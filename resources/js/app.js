const tombolSidebar = document.querySelector('[data-toggle-sidebar]');
const overlay = document.querySelector('[data-overlay]');
const lebarMobile = window.matchMedia('(max-width: 760px)');

function bukaTutupSidebar() {
    if (lebarMobile.matches) {
        document.body.classList.toggle('sidebar-terbuka');
        return;
    }

    document.body.classList.toggle('sidebar-ciut');
    localStorage.setItem('sidebar-ciut', document.body.classList.contains('sidebar-ciut') ? '1' : '0');
}

if (localStorage.getItem('sidebar-ciut') === '1' && !lebarMobile.matches) {
    document.body.classList.add('sidebar-ciut');
}

tombolSidebar?.addEventListener('click', bukaTutupSidebar);
overlay?.addEventListener('click', () => document.body.classList.remove('sidebar-terbuka'));

lebarMobile.addEventListener('change', () => {
    document.body.classList.remove('sidebar-terbuka');

    if (lebarMobile.matches) {
        document.body.classList.remove('sidebar-ciut');
    } else if (localStorage.getItem('sidebar-ciut') === '1') {
        document.body.classList.add('sidebar-ciut');
    }
});

const tombolProfil = document.querySelector('[data-toggle-profil]');
const dropdownProfil = document.querySelector('[data-dropdown-profil]');
const profilMenu = document.querySelector('[data-profil-menu]');

function tutupDropdownProfil() {
    dropdownProfil?.classList.remove('aktif');
    tombolProfil?.setAttribute('aria-expanded', 'false');
}

tombolProfil?.addEventListener('click', () => {
    const aktif = dropdownProfil?.classList.toggle('aktif');
    tombolProfil.setAttribute('aria-expanded', aktif ? 'true' : 'false');
});

document.addEventListener('click', (event) => {
    if (profilMenu && !profilMenu.contains(event.target)) {
        tutupDropdownProfil();
    }
});

const modalKonfirmasi = document.querySelector('[data-modal-konfirmasi]');
const judulKonfirmasi = modalKonfirmasi?.querySelector('[data-judul-konfirmasi]');
const pesanKonfirmasi = modalKonfirmasi?.querySelector('[data-pesan-konfirmasi]');
const tombolSetujuKonfirmasi = modalKonfirmasi?.querySelector('[data-setuju-konfirmasi]');
const tombolBatalKonfirmasi = modalKonfirmasi?.querySelector('[data-batal-konfirmasi]');
let penyelesaiKonfirmasi = null;

function tutupKonfirmasi(hasil) {
    if (!modalKonfirmasi || !penyelesaiKonfirmasi) {
        return;
    }

    modalKonfirmasi.hidden = true;
    document.body.classList.remove('modal-terbuka');
    penyelesaiKonfirmasi(hasil);
    penyelesaiKonfirmasi = null;
}

function mintaKonfirmasi({ judul, pesan, labelSetuju = 'Lanjutkan' }) {
    if (!modalKonfirmasi || !judulKonfirmasi || !pesanKonfirmasi || !tombolSetujuKonfirmasi) {
        return Promise.resolve(true);
    }

    judulKonfirmasi.textContent = judul || 'Konfirmasi aksi';
    pesanKonfirmasi.textContent = pesan || 'Pastikan data sudah benar sebelum melanjutkan.';
    tombolSetujuKonfirmasi.textContent = labelSetuju;
    modalKonfirmasi.hidden = false;
    document.body.classList.add('modal-terbuka');
    tombolSetujuKonfirmasi.focus();

    return new Promise((resolve) => {
        penyelesaiKonfirmasi = resolve;
    });
}

tombolSetujuKonfirmasi?.addEventListener('click', () => tutupKonfirmasi(true));
tombolBatalKonfirmasi?.addEventListener('click', () => tutupKonfirmasi(false));

modalKonfirmasi?.addEventListener('click', (event) => {
    if (event.target === modalKonfirmasi) {
        tutupKonfirmasi(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modalKonfirmasi && !modalKonfirmasi.hidden) {
        tutupKonfirmasi(false);
    }

    if (event.key === 'Escape') {
        tutupDropdownProfil();
    }
});

document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const setuju = await mintaKonfirmasi({
            judul: form.dataset.judulKonfirmasi,
            pesan: form.dataset.pesanKonfirmasi,
            labelSetuju: form.dataset.labelSetuju || 'Lanjutkan',
        });

        if (setuju) {
            form.submit();
        }
    });
});

document.querySelectorAll('form[data-form-closing]').forEach((form) => {
    const status = form.querySelector('[data-status-leads]');
    const sectionClosing = form.querySelector('[data-section-closing]');
    const statusAwal = status?.value;

    function perbaruiSectionClosing() {
        if (!sectionClosing || !status) {
            return;
        }

        sectionClosing.hidden = status.value !== 'Daftar';
    }

    perbaruiSectionClosing();
    status?.addEventListener('change', perbaruiSectionClosing);

    form.addEventListener('submit', async (event) => {
        if (!status || status.value !== 'Daftar' || statusAwal === 'Daftar') {
            return;
        }

        event.preventDefault();

        const setuju = await mintaKonfirmasi({
            judul: 'Closing leads?',
            pesan: 'Leads akan dipindahkan dari Data Leads ke Data Siswa. Pastikan data siswa/closing sudah benar.',
            labelSetuju: 'Closing',
        });

        if (setuju) {
            form.submit();
        }
    });
});

const inputSekolah = document.querySelector('[data-input-sekolah]');
const panelSekolah = document.querySelector('[data-panel-sekolah]');
const dataSekolah = document.querySelector('[data-data-sekolah]');

if (inputSekolah && panelSekolah && dataSekolah) {
    const sekolah = JSON.parse(dataSekolah.textContent || '[]');

    function tutupSaranSekolah() {
        panelSekolah.classList.remove('aktif');
        panelSekolah.innerHTML = '';
    }

    function tampilkanSaranSekolah() {
        const kata = inputSekolah.value.trim().toLowerCase();

        if (kata.length < 2) {
            tutupSaranSekolah();
            return;
        }

        const hasil = sekolah
            .filter((nama) => nama.toLowerCase().includes(kata))
            .slice(0, 12);

        if (hasil.length === 0) {
            tutupSaranSekolah();
            return;
        }

        panelSekolah.innerHTML = hasil
            .map((nama) => `<button class="opsi-sekolah" type="button">${nama}</button>`)
            .join('');
        panelSekolah.classList.add('aktif');
    }

    inputSekolah.addEventListener('input', tampilkanSaranSekolah);
    inputSekolah.addEventListener('focus', tampilkanSaranSekolah);

    panelSekolah.addEventListener('click', (event) => {
        const tombol = event.target.closest('.opsi-sekolah');

        if (!tombol) {
            return;
        }

        inputSekolah.value = tombol.textContent.trim();
        tutupSaranSekolah();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.autocomplete-sekolah')) {
            tutupSaranSekolah();
        }
    });
}

const inputJenjangLeads = document.querySelector('[data-jenjang-leads]');
const inputKelasLeads = document.querySelector('[data-kelas-leads]');

if (inputJenjangLeads && inputKelasLeads) {
    const opsiKelas = [...inputKelasLeads.querySelectorAll('option[data-jenjang]')];

    function perbaruiKelasLeads() {
        const jenjang = inputJenjangLeads.value;

        opsiKelas.forEach((opsi) => {
            opsi.hidden = opsi.dataset.jenjang !== jenjang;
            opsi.disabled = opsi.dataset.jenjang !== jenjang;
        });

        const kelasAktif = inputKelasLeads.selectedOptions[0];
        const kelasTidakSesuai = kelasAktif?.dataset.jenjang && kelasAktif.dataset.jenjang !== jenjang;

        if (!jenjang || jenjang === 'Gapyear' || kelasTidakSesuai) {
            inputKelasLeads.value = '';
        }

        inputKelasLeads.disabled = !jenjang || jenjang === 'Gapyear';
    }

    perbaruiKelasLeads();
    inputJenjangLeads.addEventListener('change', perbaruiKelasLeads);
}

const pilihSemua = document.querySelector('[data-pilih-semua]');
const checkboxLeads = [...document.querySelectorAll('[data-pilih-leads]')];
const formMassal = document.querySelector('[data-form-massal]');
const inputMassal = document.querySelector('[data-input-massal]');
const jumlahTerpilih = document.querySelector('[data-jumlah-terpilih]');
const tombolMassal = document.querySelector('[data-tombol-massal]');

function idLeadsTerpilih() {
    return checkboxLeads
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);
}

function perbaruiPilihanMassal() {
    if (!formMassal || !jumlahTerpilih || !tombolMassal) {
        return;
    }

    const ids = idLeadsTerpilih();
    jumlahTerpilih.textContent = ids.length.toString();
    tombolMassal.disabled = ids.length === 0;

    if (pilihSemua) {
        pilihSemua.checked = ids.length > 0 && ids.length === checkboxLeads.length;
        pilihSemua.indeterminate = ids.length > 0 && ids.length < checkboxLeads.length;
    }
}

pilihSemua?.addEventListener('change', () => {
    checkboxLeads.forEach((checkbox) => {
        checkbox.checked = pilihSemua.checked;
    });
    perbaruiPilihanMassal();
});

checkboxLeads.forEach((checkbox) => {
    checkbox.addEventListener('change', perbaruiPilihanMassal);
});

formMassal?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const ids = idLeadsTerpilih();
    const aksi = formMassal.querySelector('[name="aksi"]')?.value;

    if (ids.length === 0) {
        return;
    }

    if (aksi === 'hapus') {
        const setuju = await mintaKonfirmasi({
            judul: 'Hapus leads terpilih?',
            pesan: `Hapus ${ids.length} leads terpilih? Data yang dihapus tidak bisa dikembalikan.`,
            labelSetuju: 'Hapus',
        });

        if (!setuju) {
            return;
        }
    }

    if (!inputMassal) {
        return;
    }

    inputMassal.innerHTML = ids
        .map((id) => `<input type="hidden" name="ids[]" value="${id}">`)
        .join('');

    formMassal.submit();
});

perbaruiPilihanMassal();

document.querySelectorAll('[data-toggle-password]').forEach((tombol) => {
    tombol.addEventListener('click', () => {
        const pembungkus = tombol.closest('.bungkus-password');
        const input = pembungkus?.querySelector('[data-input-password]');

        if (!input) {
            return;
        }

        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        tombol.setAttribute('aria-pressed', tampil ? 'true' : 'false');
        tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    });
});

document.querySelectorAll('[data-form-target-kinerja]').forEach((form) => {
    const tipe = form.querySelector('[data-target-tipe]');
    const fieldCabang = form.querySelector('[data-field-target-cabang]');
    const fieldStaff = form.querySelector('[data-field-target-staff]');
    const cabang = form.querySelector('[data-target-cabang]');
    const staff = form.querySelector('[data-target-staff]');

    function perbaruiFieldTarget() {
        const targetStaff = tipe?.value === 'staff';

        if (fieldCabang && cabang) {
            fieldCabang.hidden = targetStaff;
            cabang.disabled = targetStaff;
            cabang.required = !targetStaff;
        }

        if (fieldStaff && staff) {
            fieldStaff.hidden = !targetStaff;
            staff.disabled = !targetStaff;
            staff.required = targetStaff;
        }
    }

    perbaruiFieldTarget();
    tipe?.addEventListener('change', perbaruiFieldTarget);
});
