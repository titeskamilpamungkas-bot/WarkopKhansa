/**
 * Logika keranjang kasir WARKOP KHANSA.
 * Harga dan stok selalu dihitung ulang di server (actions/proses_transaksi.php),
 * jadi perubahan di browser tidak bisa memanipulasi total pembayaran.
 */
(function () {
    const grid = document.getElementById('productGrid');
    const form = document.getElementById('formTransaksi');
    if (!grid || !form) return;

    const cartList      = document.getElementById('cartList');
    const inputKeranjang = document.getElementById('inputKeranjang');
    const ringkasanTotal = document.getElementById('ringkasanTotal');
    const ringkasanGrand = document.getElementById('ringkasanGrand');
    const inputBayar    = document.getElementById('bayar');
    const kembalianEl   = document.getElementById('kembalian');
    const hintBayar     = document.getElementById('hintBayar');
    const btnProses     = document.getElementById('btnProses');
    const cariInput     = document.getElementById('cariMenu');
    const tabs          = document.querySelectorAll('#categoryTabs .tab-btn');

    // Isi keranjang: id menu -> { id, nama, harga, stok, jumlah }
    const keranjang = new Map();
    let kategoriAktif = 'semua';

    const rupiah = function (angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    };

    function tambahItem(card) {
        const id = card.dataset.id;
        const item = keranjang.get(id) || {
            id: id,
            nama: card.dataset.nama,
            harga: Number(card.dataset.harga),
            stok: Number(card.dataset.stok),
            jumlah: 0
        };

        if (item.jumlah >= item.stok) {
            window.alert('Stok ' + item.nama + ' tidak mencukupi.');
            return;
        }

        item.jumlah++;
        keranjang.set(id, item);
        render();
    }

    function ubahJumlah(id, selisih) {
        const item = keranjang.get(id);
        if (!item) return;

        item.jumlah = Math.min(item.jumlah + selisih, item.stok);
        if (item.jumlah <= 0) {
            keranjang.delete(id);
        }
        render();
    }

    function hapusItem(id) {
        keranjang.delete(id);
        render();
    }

    function hitungTotal() {
        let total = 0;
        keranjang.forEach(function (item) {
            total += item.harga * item.jumlah;
        });
        return total;
    }

    function buatTombolQty(label, aksi) {
        const tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'qty-btn';
        tombol.textContent = label;
        tombol.addEventListener('click', aksi);
        return tombol;
    }

    // Gambar ulang isi keranjang. Semua teks dimasukkan lewat textContent agar aman dari XSS.
    function render() {
        cartList.innerHTML = '';

        if (keranjang.size === 0) {
            const kosong = document.createElement('p');
            kosong.className = 'empty-state';
            kosong.textContent = 'Keranjang masih kosong';
            cartList.appendChild(kosong);
        }

        keranjang.forEach(function (item) {
            const baris = document.createElement('div');
            baris.className = 'cart-item';

            const info = document.createElement('div');
            info.className = 'cart-info';
            const nama = document.createElement('strong');
            nama.textContent = item.nama;
            const detail = document.createElement('small');
            detail.textContent = rupiah(item.harga) + ' × ' + item.jumlah + ' = ' + rupiah(item.harga * item.jumlah);
            info.append(nama, detail);

            const kontrol = document.createElement('div');
            kontrol.className = 'qty-control';
            const jumlah = document.createElement('span');
            jumlah.textContent = item.jumlah;
            kontrol.append(
                buatTombolQty('−', function () { ubahJumlah(item.id, -1); }),
                jumlah,
                buatTombolQty('+', function () { ubahJumlah(item.id, 1); })
            );

            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'btn-icon danger';
            hapus.setAttribute('aria-label', 'Hapus dari keranjang');
            hapus.innerHTML = '<i class="fa-solid fa-trash"></i>';
            hapus.addEventListener('click', function () { hapusItem(item.id); });

            baris.append(info, kontrol, hapus);
            cartList.appendChild(baris);
        });

        const total = hitungTotal();
        ringkasanTotal.textContent = rupiah(total);
        ringkasanGrand.textContent = rupiah(total);

        // Kirim isi keranjang ke server dalam bentuk JSON (id menu dan jumlah saja)
        inputKeranjang.value = JSON.stringify(
            Array.from(keranjang.values()).map(function (item) {
                return { menu_id: Number(item.id), jumlah: item.jumlah };
            })
        );

        perbaruiBayar();
    }

    function perbaruiBayar() {
        const total = hitungTotal();
        const bayar = Number(inputBayar.value || 0);

        if (keranjang.size === 0) {
            hintBayar.textContent = 'Tambahkan menu ke keranjang terlebih dahulu.';
            kembalianEl.textContent = 'Rp 0';
            btnProses.disabled = true;
            return;
        }

        kembalianEl.textContent = rupiah(Math.max(0, bayar - total));

        if (bayar < total) {
            hintBayar.textContent = 'Nominal bayar kurang dari total.';
            btnProses.disabled = true;
        } else {
            hintBayar.textContent = '';
            btnProses.disabled = false;
        }
    }

    function filterMenu() {
        const kata = cariInput.value.trim().toLowerCase();
        grid.querySelectorAll('.product-row').forEach(function (card) {
            const cocokNama = card.dataset.nama.toLowerCase().includes(kata);
            const cocokKategori = kategoriAktif === 'semua' || card.dataset.kategori === kategoriAktif;
            card.classList.toggle('hidden', !(cocokNama && cocokKategori));
        });
    }

    // ---- Event ----
    grid.addEventListener('click', function (event) {
        const tombol = event.target.closest('.btn-tambah');
        if (tombol && !tombol.disabled) {
            tambahItem(tombol.closest('.product-row'));
        }
    });

    inputBayar.addEventListener('input', perbaruiBayar);
    cariInput.addEventListener('input', filterMenu);

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            kategoriAktif = tab.dataset.kategori;
            filterMenu();
        });
    });

    // Cegah submit ganda: tombol dinonaktifkan begitu form dikirim
    form.addEventListener('submit', function (event) {
        if (btnProses.disabled || keranjang.size === 0) {
            event.preventDefault();
            return;
        }
        btnProses.disabled = true;
        btnProses.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
    });

    render();
})();
