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
