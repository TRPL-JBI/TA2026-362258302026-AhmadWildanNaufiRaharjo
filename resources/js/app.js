import './bootstrap';

import Alpine from 'alpinejs';
import { registerKelolaChecklistTemuan } from './kelola-checklist-temuan';
import { registerPemantauanIpam } from './pemantauan-ipam';
import { registerPemantauanIpal } from './pemantauan-ipal';
import { registerPemantauanB3 } from './pemantauan-b3';
import { registerTindakLanjut } from './tindak-lanjut';
import { registerSopDokumen } from './sop-dokumen';
import { registerPatroliRiwayatOverview } from './patroli-riwayat-overview';
import { registerPatroliScan } from './patroli-scan';
import { registerPatroliTemuan } from './patroli-temuan';
import { registerPatroliApar } from './patroli-apar';
import { registerLaporanInsiden } from './laporan-insiden';
import { registerInventarisApar } from './inventaris-apar';
import { registerInventarisLokasi } from './inventaris-lokasi';
import { registerInventarisUser } from './inventaris-user';
import { registerInventarisIpam } from './inventaris-ipam';
import { registerNotifikasi } from './notifikasi';
import { registerWebPush } from './webpush';
import { registerLaporanListPage } from './list-pagination';
import { registerUiDialog } from './ui-dialog';
import { registerYearPicker } from './year-picker';

window.Alpine = Alpine;

registerUiDialog();
registerYearPicker();

registerKelolaChecklistTemuan();
registerPemantauanIpam();
registerPemantauanIpal();
registerPemantauanB3();
registerTindakLanjut();
registerSopDokumen();
registerPatroliRiwayatOverview();
registerPatroliScan();
registerPatroliTemuan();
registerPatroliApar();
registerLaporanInsiden();
registerInventarisLokasi();
registerInventarisUser();
registerInventarisApar();
registerInventarisIpam();
registerNotifikasi();
registerWebPush();
registerLaporanListPage();

Alpine.start();
