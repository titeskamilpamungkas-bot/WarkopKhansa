-- ============================================================
-- WARKOP KHANSA - Database Schema & Data Awal
-- Jalankan di phpMyAdmin (XAMPP) atau via: mysql -u root < warkop_khansa.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS warkop_khansa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE warkop_khansa;

-- Hapus tabel lama (urutan penting karena ada foreign key)
DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS menu;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS member;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- Tabel users: akun kasir dan admin/pemilik
-- ------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,           -- hasil password_hash(), bukan teks biasa
    nama          VARCHAR(100) NOT NULL,
    role          ENUM('kasir', 'admin') NOT NULL, -- admin = Admin / Pemilik
    status        ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel kategori produk
-- ------------------------------------------------------------
CREATE TABLE kategori (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori  VARCHAR(50)  NOT NULL UNIQUE,
    deskripsi      VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel menu / barang (Barang & Stok)
-- ------------------------------------------------------------
CREATE TABLE menu (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode         VARCHAR(20)   NOT NULL UNIQUE,
    nama         VARCHAR(100)  NOT NULL,
    kategori_id  INT UNSIGNED  NOT NULL,
    harga_beli   DECIMAL(12,2) NOT NULL DEFAULT 0,
    harga_jual   DECIMAL(12,2) NOT NULL,
    stok         INT          NOT NULL DEFAULT 0,
    stok_minimum INT          NOT NULL DEFAULT 5,   -- batas peringatan stok rendah
    satuan       VARCHAR(20)  NOT NULL DEFAULT 'porsi',
    status       ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_kategori FOREIGN KEY (kategori_id)
        REFERENCES kategori(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_menu_nama (nama),
    INDEX idx_menu_kategori (kategori_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel member
-- ------------------------------------------------------------
CREATE TABLE member (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_member    VARCHAR(20)  NOT NULL UNIQUE,   -- nomor identitas member
    nama            VARCHAR(100) NOT NULL,
    no_hp           VARCHAR(20)  NULL,
    alamat          VARCHAR(255) NULL,
    poin            INT UNSIGNED NOT NULL DEFAULT 0,
    status          ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    tanggal_daftar  DATE         NOT NULL,
    INDEX idx_member_nama (nama)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel transaksi (header penjualan)
-- ------------------------------------------------------------
CREATE TABLE transaksi (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi    VARCHAR(30)   NOT NULL UNIQUE,
    user_id           INT UNSIGNED  NOT NULL,        -- kasir yang melayani
    member_id         INT UNSIGNED  NULL,            -- opsional
    total_harga       DECIMAL(12,2) NOT NULL,
    diskon            DECIMAL(12,2) NOT NULL DEFAULT 0,
    grand_total       DECIMAL(12,2) NOT NULL,
    bayar             DECIMAL(12,2) NOT NULL,
    kembalian         DECIMAL(12,2) NOT NULL DEFAULT 0,
    metode_pembayaran ENUM('tunai', 'qris', 'transfer') NOT NULL DEFAULT 'tunai',
    status            ENUM('selesai', 'batal') NOT NULL DEFAULT 'selesai',
    tanggal           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_member FOREIGN KEY (member_id)
        REFERENCES member(id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_transaksi_tanggal (tanggal),
    INDEX idx_transaksi_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel detail_transaksi (item per transaksi)
-- ------------------------------------------------------------
CREATE TABLE detail_transaksi (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT UNSIGNED  NOT NULL,
    menu_id      INT UNSIGNED  NOT NULL,
    nama_menu    VARCHAR(100)  NOT NULL,            -- disalin agar riwayat tetap utuh
    harga_satuan DECIMAL(12,2) NOT NULL,
    jumlah       INT UNSIGNED  NOT NULL,
    subtotal     DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id)
        REFERENCES transaksi(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_menu FOREIGN KEY (menu_id)
        REFERENCES menu(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_detail_transaksi (transaksi_id),
    INDEX idx_detail_menu (menu_id)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL
-- Password default (WAJIB diganti setelah login pertama):
--   admin : admin123
--   kasir : kasir123
-- ============================================================

INSERT INTO users (username, password_hash, nama, role, status) VALUES
('admin', '$2y$10$Gcf1iCwC9c.t4Cp/aJQUw.7Z3PkoCQSyIcUAfF8vZWJl2whO4LRna', 'Pemilik Warkop Khansa', 'admin', 'aktif'),
('kasir', '$2y$10$6OpVs7ZpCQGYKjFXRpWPSOJXNFP.A2zsxQ/ERsTnnVbCXkW9Y7hPy', 'Kasir Warkop Khansa', 'kasir', 'aktif');

INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Rokok', 'Rokok batangan dan bungkus'),
('Kopi', 'Minuman kopi'),
('Minuman', 'Minuman non-kopi'),
('Makanan', 'Makanan dan mie instan'),
('Gas', 'Tabung gas'),
('Galon', 'Air galon');

INSERT INTO menu (kode, nama, kategori_id, harga_beli, harga_jual, stok, stok_minimum, satuan) VALUES
('RKK-001', 'Rokok Sampoerna', 1, 30000, 35000, 12, 5, 'bungkus'),
('RKK-002', 'Rokok Djarum',    1, 25000, 30000,  8, 5, 'bungkus'),
('KPI-001', 'Kopi Hitam',      2,  4000,  8000, 15, 5, 'gelas'),
('KPI-002', 'Latte',           2,  5500, 10000, 10, 5, 'gelas'),
('MNM-001', 'Es Teh',          3,  1500,  4000, 20, 5, 'gelas'),
('MKN-001', 'Indomie Goreng',  4,  2500,  3000, 20, 5, 'pcs'),
('MKN-002', 'Roti Bakar',      4,  3000,  6000, 12, 5, 'porsi'),
('GAS-001', 'Gas 3kg',         5, 15000, 18000, 10, 5, 'pcs'),
('GLN-001', 'Aqua Galon',      6, 15000, 18000,  5, 5, 'pcs');

INSERT INTO member (nomor_member, nama, no_hp, alamat, poin, status, tanggal_daftar) VALUES
('MBR-0001', 'Andi Pratama',   '081234561234', NULL, 50, 'aktif',    '2026-09-12'),
('MBR-0002', 'Siti Nurhaliza', '081356785678', NULL, 30, 'aktif',    '2026-09-15'),
('MBR-0003', 'Budi Santoso',   '082112349012', NULL, 70, 'aktif',    '2026-09-20'),
('MBR-0004', 'Citra Lestari',  '083155533300', NULL, 20, 'aktif',    '2026-10-02'),
('MBR-0005', 'Dika Pratama',   '081044440055', NULL, 10, 'aktif',    '2026-10-10'),
('MBR-0006', 'Fajar Maulana',  '085244448888', NULL,  5, 'nonaktif', '2026-10-15');
