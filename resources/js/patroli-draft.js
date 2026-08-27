import { restoreDraftPhotoList, stripPhotoFilesForDraft } from './patroli-photo';

/** Key lama — dibersihkan otomatis agar draft korup tidak ikut dimuat lagi. */
const LEGACY_APAR_DRAFT_KEY = 'safety_patrol.patroli.apar.draft';

const STORAGE_KEYS = {
    temuan: 'safety_patrol.patroli.temuan.draft',
    apar: 'safety_patrol.patroli.apar.draft.v2',
};

function purgeLegacyAparDraft() {
    try {
        sessionStorage.removeItem(LEGACY_APAR_DRAFT_KEY);
    } catch {
        /* mode privat / storage tidak tersedia */
    }
}

function readJson(key) {
    try {
        const raw = sessionStorage.getItem(key);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function writeJson(key, value) {
    try {
        sessionStorage.setItem(key, JSON.stringify(value));
    } catch {
        /* quota penuh / mode privat — abaikan */
    }
}

function restoreTemuanDraftSections(sections) {
    if (!Array.isArray(sections)) {
        return [];
    }

    return sections.map((section) => {
        if (section?.saved) {
            return section;
        }

        return {
            ...section,
            items: Array.isArray(section.items)
                ? section.items.map((item) => ({
                      ...item,
                      fotoDokumentasi: restoreDraftPhotoList(item.fotoDokumentasi),
                  }))
                : [],
        };
    });
}

function restoreAparDraftSections(lokasiSections) {
    if (!Array.isArray(lokasiSections)) {
        return [];
    }

    return lokasiSections.map((lokasi) => ({
        ...lokasi,
        aparList: Array.isArray(lokasi.aparList)
            ? lokasi.aparList.map((apar) => {
                  if (apar?.saved) {
                      return apar;
                  }

                  return {
                      ...apar,
                      fotoKondisi: restoreDraftPhotoList(apar.fotoKondisi),
                  };
              })
            : [],
    }));
}

export function loadTemuanDraft() {
    const data = readJson(STORAGE_KEYS.temuan);

    return restoreTemuanDraftSections(Array.isArray(data) ? data : []);
}

export function saveTemuanDraft(sections) {
    writeJson(STORAGE_KEYS.temuan, stripPhotoFilesForDraft(sections));
}

export function clearTemuanDraft() {
    sessionStorage.removeItem(STORAGE_KEYS.temuan);
}

export function loadAparDraft() {
    purgeLegacyAparDraft();

    const data = readJson(STORAGE_KEYS.apar);

    return restoreAparDraftSections(Array.isArray(data) ? data : []);
}

export function saveAparDraft(lokasiSections) {
    writeJson(STORAGE_KEYS.apar, stripPhotoFilesForDraft(lokasiSections));
}

export function clearAparDraft() {
    purgeLegacyAparDraft();
    sessionStorage.removeItem(STORAGE_KEYS.apar);
}

/** Mulai patroli baru dari menu riwayat / dashboard. */
export function clearPatroliDrafts() {
    clearTemuanDraft();
    clearAparDraft();
}
