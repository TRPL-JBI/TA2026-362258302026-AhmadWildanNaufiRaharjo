import ExcelJS from 'exceljs';
import { renderAsync } from 'docx-preview';

function clearElement(element) {
    if (!element) {
        return;
    }

    element.replaceChildren();
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function bytesToBase64(input) {
    const bytes = input instanceof Uint8Array ? input : new Uint8Array(input);
    let binary = '';
    const chunkSize = 0x8000;

    for (let index = 0; index < bytes.length; index += chunkSize) {
        binary += String.fromCharCode(...bytes.subarray(index, index + chunkSize));
    }

    return window.btoa(binary);
}

function imageMimeType(extension) {
    switch (String(extension || '').toLowerCase()) {
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'png':
            return 'image/png';
        case 'gif':
            return 'image/gif';
        case 'webp':
            return 'image/webp';
        default:
            return null;
    }
}

function argbToCss(argb) {
    if (!argb || typeof argb !== 'string' || argb.length !== 8) {
        return null;
    }

    const alpha = Number.parseInt(argb.slice(0, 2), 16) / 255;
    const red = Number.parseInt(argb.slice(2, 4), 16);
    const green = Number.parseInt(argb.slice(4, 6), 16);
    const blue = Number.parseInt(argb.slice(6, 8), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
}

function decodeAddress(address) {
    const match = /^([A-Z]+)(\d+)$/.exec(address);

    if (!match) {
        return null;
    }

    let col = 0;

    for (const char of match[1]) {
        col = (col * 26) + (char.charCodeAt(0) - 64);
    }

    return {
        row: Number.parseInt(match[2], 10),
        col,
    };
}

function buildMergeLookup(merges) {
    const master = new Map();
    const covered = new Set();

    for (const merge of merges) {
        const [topLeft, bottomRight] = merge.split(':');
        const start = decodeAddress(topLeft);
        const end = decodeAddress(bottomRight);

        if (!start || !end) {
            continue;
        }

        master.set(`${start.row}:${start.col}`, {
            rowspan: end.row - start.row + 1,
            colspan: end.col - start.col + 1,
        });

        for (let row = start.row; row <= end.row; row += 1) {
            for (let col = start.col; col <= end.col; col += 1) {
                if (row !== start.row || col !== start.col) {
                    covered.add(`${row}:${col}`);
                }
            }
        }
    }

    return { master, covered };
}

function fontCssFromFont(font = {}) {
    const styles = [];

    // ExcelJS sering salah membaca bold/italic/strike/underline dari file
    // yang digenerate PhpSpreadsheet (semua sel jadi true). Jangan dipakai.
    if (typeof font.size === 'number' && font.size > 0) {
        styles.push(`font-size:${font.size}pt`);
    }

    if (typeof font.name === 'string' && font.name.trim() !== '') {
        styles.push(`font-family:${font.name}, Calibri, Arial, sans-serif`);
    }

    // Hanya warna ARGB eksplisit; theme color tidak diterjemahkan di sini.
    const fontColor = argbToCss(font.color?.argb);

    if (fontColor) {
        styles.push(`color:${fontColor}`);
    }

    return styles.join(';');
}

function getCellDisplayHtml(cell) {
    if (!cell || cell.value === null || cell.value === undefined) {
        return '';
    }

    const value = cell.value;

    if (typeof value === 'object') {
        if (Array.isArray(value.richText)) {
            return value.richText.map((part) => {
                const text = escapeHtml(part?.text || '');
                const style = fontCssFromFont(part?.font || {});

                return style ? `<span style="${style}">${text}</span>` : text;
            }).join('');
        }

        if (value.text) {
            return escapeHtml(String(value.text));
        }

        if (value.result !== null && value.result !== undefined) {
            return escapeHtml(String(value.result));
        }

        if (value.hyperlink) {
            return escapeHtml(String(value.text || value.hyperlink));
        }

        if (value instanceof Date) {
            return escapeHtml(value.toLocaleDateString('id-ID'));
        }
    }

    return escapeHtml(String(value));
}

function cellStyle(cell) {
    const styles = [];
    const value = cell?.value;
    const isRichText = value && typeof value === 'object' && Array.isArray(value.richText);

    // Rich text sudah punya style per potongan teks; jangan timpa dengan font sel.
    if (!isRichText) {
        const fontStyle = fontCssFromFont(cell.font || {});

        if (fontStyle) {
            styles.push(fontStyle);
        }
    }

    if (cell.alignment?.horizontal) {
        styles.push(`text-align:${cell.alignment.horizontal}`);
    }

    if (cell.alignment?.vertical) {
        styles.push(`vertical-align:${cell.alignment.vertical}`);
    }

    if (cell.alignment?.wrapText) {
        styles.push('white-space:pre-wrap');
    }

    const fillColor = argbToCss(cell.fill?.fgColor?.argb) || argbToCss(cell.fill?.bgColor?.argb);

    if (fillColor) {
        styles.push(`background-color:${fillColor}`);
    }

    for (const side of ['top', 'right', 'bottom', 'left']) {
        const border = cell.border?.[side];

        if (border?.style) {
            const borderColor = argbToCss(border.color?.argb) || '#cbd5e1';
            styles.push(`border-${side}:1px solid ${borderColor}`);
        }
    }

    return styles.join(';');
}

function worksheetBounds(worksheet, mergeLookup) {
    let maxRow = 0;
    let maxColWithContent = 0;

    worksheet.eachRow({ includeEmpty: false }, (row, rowNumber) => {
        row.eachCell({ includeEmpty: false }, (cell, colNumber) => {
            const hasValue = cell.value !== null && cell.value !== undefined && cell.value !== '';

            maxRow = Math.max(maxRow, rowNumber);

            if (hasValue) {
                maxColWithContent = Math.max(maxColWithContent, colNumber);
            }
        });
    });

    for (const key of mergeLookup.master.keys()) {
        const [row, col] = key.split(':').map(Number);
        const span = mergeLookup.master.get(key);
        const mergeEndCol = col + span.colspan - 1;

        maxRow = Math.max(maxRow, row + span.rowspan - 1);

        if (col <= maxColWithContent) {
            maxColWithContent = Math.max(maxColWithContent, mergeEndCol);
        }
    }

    return { maxRow, maxCol: maxColWithContent || 10 };
}

function renderWorksheetImages(worksheet, workbook) {
    if (typeof worksheet.getImages !== 'function') {
        return '';
    }

    const images = worksheet.getImages()
        .filter((image) => ((image.range?.tl?.nativeRow ?? -1) + 1) >= 13)
        .map((image, index) => {
            const media = workbook.model?.media?.[image.imageId];
            const mimeType = imageMimeType(media?.extension);
            const buffer = media?.buffer;

            if (!mimeType || !buffer) {
                return '';
            }

            const src = `data:${mimeType};base64,${bytesToBase64(buffer)}`;

            return `
                <figure class="laporan-xlsx-image-card">
                    <img src="${src}" alt="Foto laporan ${index + 1}" class="laporan-xlsx-image">
                    <figcaption class="laporan-xlsx-image-caption">Foto ${index + 1}</figcaption>
                </figure>
            `;
        })
        .filter(Boolean);

    if (images.length === 0) {
        return '';
    }

    return `
        <section class="laporan-xlsx-images-section">
            <div class="laporan-xlsx-images-title">Foto Laporan</div>
            <div class="laporan-xlsx-images-grid">${images.join('')}</div>
        </section>
    `;
}

function renderWorksheet(worksheet, workbook) {
    const mergeLookup = buildMergeLookup(worksheet.model?.merges || []);
    const { maxRow, maxCol } = worksheetBounds(worksheet, mergeLookup);

    if (maxRow === 0 || maxCol === 0) {
        return '';
    }

    let colgroup = '<colgroup>';

    for (let col = 1; col <= maxCol; col += 1) {
        const column = worksheet.getColumn(col);
        const rawWidth = column.width ? Math.round(column.width * 7) : 64;
        const width = `${Math.max(rawWidth, 14)}px`;
        colgroup += `<col style="width:${width}">`;
    }

    colgroup += '</colgroup>';

    let rows = '';

    for (let rowNumber = 1; rowNumber <= maxRow; rowNumber += 1) {
        const row = worksheet.getRow(rowNumber);
        const rawHeight = row.height ? Math.min(Math.round(row.height), 200) : null;
        const rowHeight = rawHeight ? ` style="height:${rawHeight}px"` : '';
        let cells = '';

        for (let colNumber = 1; colNumber <= maxCol; colNumber += 1) {
            const key = `${rowNumber}:${colNumber}`;

            if (mergeLookup.covered.has(key)) {
                continue;
            }

            const cell = worksheet.getCell(rowNumber, colNumber);
            const span = mergeLookup.master.get(key);
            let attrs = '';

            if (span?.colspan > 1) {
                attrs += ` colspan="${span.colspan}"`;
            }

            if (span?.rowspan > 1) {
                attrs += ` rowspan="${span.rowspan}"`;
            }

            const style = cellStyle(cell);

            if (style) {
                attrs += ` style="${style}"`;
            }

            cells += `<td${attrs}>${getCellDisplayHtml(cell)}</td>`;
        }

        rows += `<tr${rowHeight}>${cells}</tr>`;
    }

    const images = renderWorksheetImages(worksheet, workbook);

    return `
        <div class="laporan-xlsx-sheet-inner">
            <table class="laporan-xlsx-table">${colgroup}<tbody>${rows}</tbody></table>
            ${images}
        </div>
    `;
}

async function renderXlsxPreview(blob, xlsxBodyEl) {
    const workbook = new ExcelJS.Workbook();
    await workbook.xlsx.load(await blob.arrayBuffer());

    const sheets = workbook.worksheets
        .map((worksheet) => {
            const table = renderWorksheet(worksheet, workbook);

            if (!table) {
                return '';
            }

            const title = workbook.worksheets.length > 1
                ? `<div class="laporan-xlsx-sheet-title">${escapeHtml(worksheet.name)}</div>`
                : '';

            return `<section class="laporan-xlsx-sheet">${title}${table}</section>`;
        })
        .filter(Boolean);

    if (sheets.length === 0) {
        throw new Error('Sheet laporan kosong.');
    }

    xlsxBodyEl.innerHTML = sheets.join('');
}

export async function renderLaporanPreview(blob, format, { bodyEl, styleEl, xlsxBodyEl, docxWrapEl, xlsxWrapEl }) {
    const normalizedFormat = String(format || '').toLowerCase();

    clearElement(bodyEl);
    clearElement(styleEl);
    clearElement(xlsxBodyEl);

    if (normalizedFormat === '.docx') {
        docxWrapEl?.classList.remove('hidden');
        xlsxWrapEl?.classList.add('hidden');

        await renderAsync(blob, bodyEl, styleEl, {
            className: 'docx',
            inWrapper: true,
            ignoreWidth: false,
            ignoreHeight: false,
            ignoreFonts: false,
            breakPages: true,
            ignoreLastRenderedPageBreak: false,
            experimental: true,
            trimXmlDeclaration: true,
            useBase64URL: true,
        });

        return;
    }

    if (normalizedFormat === '.xlsx') {
        docxWrapEl?.classList.add('hidden');
        xlsxWrapEl?.classList.remove('hidden');

        await renderXlsxPreview(blob, xlsxBodyEl);

        return;
    }

    throw new Error('Format laporan tidak didukung untuk preview.');
}
