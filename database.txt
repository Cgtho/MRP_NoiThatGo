-- ==========================================================
-- HỆ THỐNG QUẢN LÝ KHO & SẢN XUẤT ĐỒ GỖ NỘI THẤT
-- PHP + MySQL / MariaDB
-- ==========================================================

CREATE DATABASE IF NOT EXISTS quanlykho_noithat
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE quanlykho_noithat;


-- ==========================================================
-- 1. BẢNG DONVITINH
-- ==========================================================

CREATE TABLE IF NOT EXISTS DONVITINH (
    maDVT INT AUTO_INCREMENT PRIMARY KEY,
    tenDVT VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 2. BẢNG NGUYENVATLIEUXUONG
-- ==========================================================

CREATE TABLE IF NOT EXISTS NGUYENVATLIEUXUONG (
    maNVL VARCHAR(10) PRIMARY KEY,
    tenNVL VARCHAR(50) NOT NULL,
    maDVT INT NOT NULL,
    soLuong INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_nvlx_dvt
        FOREIGN KEY (maDVT)
        REFERENCES DONVITINH(maDVT)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 2.1 BẢNG NGUYENVATLIEU
-- ==========================================================

CREATE TABLE IF NOT EXISTS NGUYENVATLIEU (
    maNVL VARCHAR(10) PRIMARY KEY,
    tenNVL VARCHAR(50) NOT NULL,
    maDVT INT NOT NULL,
    soLuong INT NOT NULL DEFAULT 0,
    isDeleted TINYINT NOT NULL DEFAULT 1,
    trangThai TINYINT NOT NULL DEFAULT 1,

    CONSTRAINT fk_nvl_dvt
        FOREIGN KEY (maDVT)
        REFERENCES DONVITINH(maDVT)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 3. BẢNG NHANVIEN
-- vaiTro:
-- 0 = Quản lý kho
-- 1 = Nhân viên kho
-- 2 = Quản trị hệ thống
-- trangThai:
-- 0 = Đã khóa
-- 1 = Hoạt động
--
-- ==========================================================

CREATE TABLE IF NOT EXISTS NHANVIEN (
    maNV VARCHAR(10) PRIMARY KEY,
    hoTen VARCHAR(50) NOT NULL,
    vaiTro TINYINT NOT NULL DEFAULT 1,
    trangThai TINYINT NOT NULL DEFAULT 1,
    matKhau VARCHAR(255) NOT NULL,
    sdt VARCHAR(12) NULL,
    diaChi VARCHAR(50) NULL
) ENGINE=InnoDB;

-- ==========================================================
-- 4. BẢNG THANHPHAM
-- ==========================================================

CREATE TABLE IF NOT EXISTS THANHPHAM (
    maTP VARCHAR(20) PRIMARY KEY,
    tenTP VARCHAR(50) NOT NULL,
    donViTinh VARCHAR(20) NOT NULL,
    soLuong INT NOT NULL DEFAULT 0,
    isDeleted TINYINT NOT NULL DEFAULT 1,
    trangThai TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;


-- ==========================================================
-- 5. BẢNG CHITIETTHANHPHAM
-- Định mức nguyên vật liệu cấu thành thành phẩm
-- ==========================================================

CREATE TABLE IF NOT EXISTS CHITIETTHANHPHAM (
    maTP VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong INT NOT NULL,

    PRIMARY KEY (maTP, maNVL),

    CONSTRAINT fk_cttp_tp
        FOREIGN KEY (maTP)
        REFERENCES THANHPHAM(maTP)
        ON DELETE CASCADE,

    CONSTRAINT fk_cttp_nvl
        FOREIGN KEY (maNVL)
        REFERENCES NGUYENVATLIEU(maNVL)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 6. BẢNG PHIEUNHAPKHO
-- ==========================================================

CREATE TABLE IF NOT EXISTS PHIEUNHAPKHO (
    maPN VARCHAR(20) PRIMARY KEY,
    ngayNhap DATETIME NOT NULL,
    ghiChu VARCHAR(255) NULL
) ENGINE=InnoDB;


-- ==========================================================
-- 7. BẢNG CHITIETPHIEUNHAP
-- ==========================================================

CREATE TABLE IF NOT EXISTS CHITIETPHIEUNHAP (
    maPN VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong INT NOT NULL,

    PRIMARY KEY (maPN, maNVL),

    CONSTRAINT fk_ctpn_pn
        FOREIGN KEY (maPN)
        REFERENCES PHIEUNHAPKHO(maPN)
        ON DELETE CASCADE,

    CONSTRAINT fk_ctpn_nvl
        FOREIGN KEY (maNVL)
        REFERENCES NGUYENVATLIEU(maNVL)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 8. BẢNG PHIEUXUATNVL
-- loaiPhieu = 0: Phiếu xuất nguyên vật liệu
-- ==========================================================

CREATE TABLE IF NOT EXISTS PHIEUXUATNVL (
    maPX VARCHAR(20) PRIMARY KEY,
    ngayXuat DATETIME NOT NULL,
    trangThai BOOLEAN NOT NULL DEFAULT 0,
    maNV VARCHAR(10) NOT NULL,
    maQL VARCHAR(10) NULL,
    ngayDuyet DATETIME NULL,
    ghiChu VARCHAR(255) NULL,
    loaiPhieu BOOLEAN NOT NULL DEFAULT 0,

    CONSTRAINT fk_pxnvl_nv
        FOREIGN KEY (maNV)
        REFERENCES NHANVIEN(maNV)
        ON UPDATE CASCADE,

    CONSTRAINT fk_pxnvl_ql
        FOREIGN KEY (maQL)
        REFERENCES NHANVIEN(maNV)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 9. BẢNG CHITIETPHIEUXUATNVL
-- ==========================================================

CREATE TABLE IF NOT EXISTS CHITIETPHIEUXUATNVL (
    maPX VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong INT NOT NULL,

    PRIMARY KEY (maPX, maNVL),

    CONSTRAINT fk_ctpxnvl_px
        FOREIGN KEY (maPX)
        REFERENCES PHIEUXUATNVL(maPX)
        ON DELETE CASCADE,

    CONSTRAINT fk_ctpxnvl_nvl
        FOREIGN KEY (maNVL)
        REFERENCES NGUYENVATLIEU(maNVL)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 10. BẢNG PHIEUXUATTP
-- loaiPhieu = 1: Phiếu xuất thành phẩm
-- trangThai: 0 = Chờ duyệt, 1 = Đã duyệt (đã trừ kho), 2 = Từ chối
-- ==========================================================

CREATE TABLE IF NOT EXISTS PHIEUXUATTP (
    maPX VARCHAR(20) PRIMARY KEY,
    ngayXuat DATETIME NOT NULL,
    trangThai TINYINT NOT NULL DEFAULT 0,
    maNV VARCHAR(10) NOT NULL,
    maQL VARCHAR(10),
    loaiPhieu BOOLEAN NOT NULL DEFAULT 1,
    ngayDuyet DATETIME NULL,
    lyDoTuChoi VARCHAR(255) NULL,

    CONSTRAINT fk_pxtp_nv
        FOREIGN KEY (maNV)
        REFERENCES NHANVIEN(maNV)
        ON UPDATE CASCADE,

    CONSTRAINT fk_pxtp_ql
        FOREIGN KEY (maQL)
        REFERENCES NHANVIEN(maNV)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 11. BẢNG CHITIETPHIEUXUATTP
-- ==========================================================

CREATE TABLE IF NOT EXISTS CHITIETPHIEUXUATTP (
    maPX VARCHAR(20) NOT NULL,
    maTP VARCHAR(20) NOT NULL,
    soLuong INT NOT NULL,

    PRIMARY KEY (maPX, maTP),

    CONSTRAINT fk_ctpxtp_px
        FOREIGN KEY (maPX)
        REFERENCES PHIEUXUATTP(maPX)
        ON DELETE CASCADE,

    CONSTRAINT fk_ctpxtp_tp
        FOREIGN KEY (maTP)
        REFERENCES THANHPHAM(maTP)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 12. BẢNG YEUCAU
-- Lệnh sản xuất từ quản lý
-- trangThai:
-- 0 = Chờ xử lý (chưa đủ nguyên vật liệu)
-- 1 = Đang làm (đã đủ nguyên vật liệu)
-- 2 = Đã hoàn thành
-- ==========================================================

CREATE TABLE IF NOT EXISTS YEUCAU (
    maYC VARCHAR(20) PRIMARY KEY,
    ngayYC DATETIME NOT NULL,
    maNV VARCHAR(10) NOT NULL,
    trangThai TINYINT NOT NULL DEFAULT 0,

    CONSTRAINT fk_yc_nv
        FOREIGN KEY (maNV)
        REFERENCES NHANVIEN(maNV)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 13. BẢNG CHITIETYEUCAU
-- ==========================================================

CREATE TABLE IF NOT EXISTS CHITIETYEUCAU (
    maYC VARCHAR(20) NOT NULL,
    maTP VARCHAR(20) NOT NULL,
    soLuong INT NOT NULL,

    PRIMARY KEY (maYC, maTP),

    CONSTRAINT fk_ctyc_yc
        FOREIGN KEY (maYC)
        REFERENCES YEUCAU(maYC)
        ON DELETE CASCADE,

    CONSTRAINT fk_ctyc_tp
        FOREIGN KEY (maTP)
        REFERENCES THANHPHAM(maTP)
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ==========================================================
-- 13.1 BỔ SUNG TRẠNG THÁI DỮ LIỆU GỐC
-- isDeleted:
--   1 = Chưa từng được sử dụng, có thể xóa cứng
--   0 = Đã được sử dụng, không được xóa cứng
-- trangThai:
--   1 = Đang hiển thị/sử dụng
--   0 = Đang ẩn/ngừng sử dụng
--
-- ==========================================================

-- Khóa xóa cứng các nguyên vật liệu/thành phẩm đã có lịch sử sử dụng.
UPDATE NGUYENVATLIEU AS nvl
SET isDeleted = 0
WHERE EXISTS (SELECT 1 FROM CHITIETTHANHPHAM ct WHERE ct.maNVL = nvl.maNVL)
   OR EXISTS (SELECT 1 FROM CHITIETPHIEUNHAP ct WHERE ct.maNVL = nvl.maNVL)
   OR EXISTS (SELECT 1 FROM CHITIETPHIEUXUATNVL ct WHERE ct.maNVL = nvl.maNVL);

UPDATE THANHPHAM AS tp
SET isDeleted = 0
WHERE EXISTS (SELECT 1 FROM CHITIETTHANHPHAM ct WHERE ct.maTP = tp.maTP)
   OR EXISTS (SELECT 1 FROM CHITIETPHIEUXUATTP ct WHERE ct.maTP = tp.maTP)
   OR EXISTS (SELECT 1 FROM CHITIETYEUCAU ct WHERE ct.maTP = tp.maTP);


-- ==========================================================
-- 13.2 TỰ ĐỘNG KHÓA XÓA KHI PHÁT SINH QUAN HỆ
-- Khi NVL/thành phẩm được ghi nhận trong bảng chi tiết,
-- isDeleted của dữ liệu gốc tự động chuyển thành 0.
-- ==========================================================

DELIMITER //

DROP TRIGGER IF EXISTS trg_cttp_mark_material_used//
DROP TRIGGER IF EXISTS trg_cttp_mark_product_used//
DROP TRIGGER IF EXISTS trg_ctpn_mark_material_used//
DROP TRIGGER IF EXISTS trg_ctpxnvl_mark_material_used//
DROP TRIGGER IF EXISTS trg_ctpxtp_mark_product_used//
DROP TRIGGER IF EXISTS trg_ctyc_mark_product_used//

CREATE TRIGGER trg_cttp_mark_material_used
AFTER INSERT ON CHITIETTHANHPHAM
FOR EACH ROW
BEGIN
    UPDATE NGUYENVATLIEU
    SET isDeleted = 0
    WHERE maNVL = NEW.maNVL;
END//

CREATE TRIGGER trg_cttp_mark_product_used
AFTER INSERT ON CHITIETTHANHPHAM
FOR EACH ROW
BEGIN
    UPDATE THANHPHAM
    SET isDeleted = 0
    WHERE maTP = NEW.maTP;
END//

CREATE TRIGGER trg_ctpn_mark_material_used
AFTER INSERT ON CHITIETPHIEUNHAP
FOR EACH ROW
BEGIN
    UPDATE NGUYENVATLIEU
    SET isDeleted = 0
    WHERE maNVL = NEW.maNVL;
END//

CREATE TRIGGER trg_ctpxnvl_mark_material_used
AFTER INSERT ON CHITIETPHIEUXUATNVL
FOR EACH ROW
BEGIN
    UPDATE NGUYENVATLIEU
    SET isDeleted = 0
    WHERE maNVL = NEW.maNVL;
END//

CREATE TRIGGER trg_ctpxtp_mark_product_used
AFTER INSERT ON CHITIETPHIEUXUATTP
FOR EACH ROW
BEGIN
    UPDATE THANHPHAM
    SET isDeleted = 0
    WHERE maTP = NEW.maTP;
END//

CREATE TRIGGER trg_ctyc_mark_product_used
AFTER INSERT ON CHITIETYEUCAU
FOR EACH ROW
BEGIN
    UPDATE THANHPHAM
    SET isDeleted = 0
    WHERE maTP = NEW.maTP;
END//

DELIMITER ;


-- ==========================================================
-- ==================== DỮ LIỆU MẪU =========================
-- ==========================================================


-- ==========================================================
-- 14. ĐƠN VỊ TÍNH
-- ==========================================================

INSERT INTO DONVITINH (maDVT, tenDVT) VALUES
(1, 'Kg'),
(2, 'Lít'),
(3, 'Cái'),
(4, 'Mét'),
(5, 'Tấm'),
(6, 'Cuộn');


-- ==========================================================
-- 15. NHÂN VIÊN
-- 1 quản trị hệ thống, 8 nhân viên kho, 5 quản lý
-- ==========================================================
INSERT INTO NHANVIEN (maNV, hoTen, vaiTro, trangThai, matKhau, sdt, diaChi) VALUES
-- Quản trị hệ thống (vaiTro = 2)
('AD001', 'Quản trị hệ thống', 2, 1, '$2y$10$Y4eKaLRT1eyVfVbpLBp2EeHiGuxI1gspMknmA96IwG5Q.r2vnipbi', NULL, NULL),

-- Quản lý / Trưởng kho (vaiTro = 0)
('QL001', 'Lê Minh Hoàng', 0, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0902000001', 'Quận 5, TP.HCM'),
('QL002', 'Phạm Quốc Huy', 0, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0902000002', 'Quận 10, TP.HCM'),
('QL003', 'Nguyễn Thị Thanh', 0, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0902000003', 'Quận 7, TP.HCM'),

-- Quản lý đã khóa (trangThai = 0), vẫn được giữ để bảo toàn lịch sử duyệt
('QL004', 'Đỗ Văn Nam', 0, 0, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0902000004', 'Thủ Đức, TP.HCM'),
('QL005', 'Bùi Minh Tâm', 0, 0, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0902000005', 'Gò Vấp, TP.HCM'),

-- Nhân viên / Thủ kho / Kiểm kê (vaiTro = 1, trangThai = 1: Đang làm việc)
('NV001', 'Nguyễn Văn An', 1, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000001', 'Quận 1, TP.HCM'),
('NV002', 'Trần Văn Bình', 1, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000002', 'Quận 3, TP.HCM'),
('NV003', 'Võ Thị Mai', 1, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000003', 'Tân Bình, TP.HCM'),
('NV005', 'Phan Văn Dũng', 1, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000005', 'Quận 6, TP.HCM'),
('NV006', 'Lê Thị Hạnh', 1, 1, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000006', 'Quận 8, TP.HCM'),

-- Tài khoản đã bị khóa (trangThai = 0)
('NV004', 'Hoàng Văn Cường', 1, 0, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000004', 'Bình Thạnh, TP.HCM'),
('NV007', 'Đặng Quốc Bảo', 1, 0, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000007', 'Bình Tân, TP.HCM'),
('NV008', 'Nguyễn Thị Lan', 1, 0, '$2y$10$pSJ5.rDmJcfqlKGaFoLeSeYIPZ2ubUAIycCB1PhfIIWYZScctuuy6', '0901000008', 'Phú Nhuận, TP.HCM');


-- ==========================================================
-- 16. 37 NGUYÊN VẬT LIỆU
-- ==========================================================

INSERT INTO NGUYENVATLIEU
(maNVL, tenNVL, maDVT, soLuong, isDeleted, trangThai) VALUES

('NVL001', 'Gỗ MDF 18mm',        5, 500, 0, 1),
('NVL002', 'Gỗ MDF 9mm',         5, 300, 0, 1),
('NVL003', 'Gỗ cao su',          5, 400, 0, 1),
('NVL004', 'Gỗ thông',           5, 350, 0, 1),
('NVL005', 'Gỗ sồi',             5, 250, 0, 1),
('NVL006', 'Ván ép',             5, 300, 0, 1),
('NVL007', 'Veneer gỗ sồi',      4, 200, 0, 1),
('NVL008', 'Laminate',           5, 250, 0, 1),

('NVL009', 'Sơn PU trắng',       2, 150, 0, 1),
('NVL010', 'Sơn PU nâu',         2, 180, 0, 1),
('NVL011', 'Sơn PU đen',         2, 120, 0, 1),
('NVL012', 'Sơn PU trong',       2, 100, 0, 1),

('NVL013', 'Keo dán gỗ',         2, 120, 0, 1),
('NVL014', 'Keo AB',             2, 80, 0, 1),
('NVL015', 'Vít gỗ 4x40',        3, 3000, 0, 1),
('NVL016', 'Vít gỗ 5x50',        3, 2500, 0, 1),

('NVL017', 'Bản lề tủ',          3, 800, 0, 1),
('NVL018', 'Ray trượt ngăn kéo', 3, 600, 0, 1),
('NVL019', 'Tay nắm tủ',         3, 700, 0, 1),
('NVL020', 'Chân bàn',           3, 400, 0, 1),

('NVL021', 'Chân ghế',           3, 500, 0, 1),
('NVL022', 'Đệm mút',            5, 300, 0, 1),
('NVL023', 'Vải bọc ghế',        4, 400, 0, 1),
('NVL024', 'Kính cường lực',     5, 200, 0, 1),
('NVL025', 'Ốc lục giác',        3, 0, 0, 1);


-- ==========================================================
-- 17. 17 THÀNH PHẨM ĐỒ GỖ NỘI THẤT
-- ==========================================================

INSERT INTO THANHPHAM
(maTP, tenTP, donViTinh, soLuong, isDeleted, trangThai) VALUES

('TP001', 'Bàn làm việc gỗ',    'Cái', 30, 0, 1),
('TP002', 'Bàn ăn gỗ',          'Cái', 25, 0, 1),
('TP003', 'Ghế ăn gỗ',          'Cái', 40, 0, 1),
('TP004', 'Ghế văn phòng gỗ',   'Cái', 25, 0, 1),
('TP005', 'Tủ quần áo gỗ',     'Cái', 15, 0, 1),
('TP006', 'Tủ hồ sơ gỗ',        'Cái', 20, 0, 1),
('TP007', 'Kệ sách gỗ',         'Cái', 25, 0, 1),
('TP008', 'Kệ tivi gỗ',         'Cái', 20, 0, 1),
('TP009', 'Giường ngủ gỗ',      'Cái', 15, 0, 1),
('TP010', 'Bàn trang điểm gỗ',  'Cái', 15, 0, 1),
('TP011', 'Ván lót gỗ',         'Cái', 0, 0, 1);


-- ==========================================================
-- 18. ĐỊNH MỨC NGUYÊN VẬT LIỆU
-- Mỗi thành phẩm có 4 loại NVL
-- ==========================================================

-- Bàn làm việc
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP001', 'NVL001', 2),
('TP001', 'NVL003', 1),
('TP001', 'NVL015', 12),
('TP001', 'NVL020', 4);

-- Bàn ăn
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP002', 'NVL003', 3),
('TP002', 'NVL005', 1),
('TP002', 'NVL015', 16),
('TP002', 'NVL010', 1);

-- Ghế ăn
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP003', 'NVL003', 1),
('TP003', 'NVL021', 4),
('TP003', 'NVL015', 8),
('TP003', 'NVL010', 1);

-- Ghế văn phòng
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP004', 'NVL003', 1),
('TP004', 'NVL022', 2),
('TP004', 'NVL023', 2),
('TP004', 'NVL015', 10);

-- Tủ quần áo
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP005', 'NVL001', 6),
('TP005', 'NVL002', 2),
('TP005', 'NVL017', 6),
('TP005', 'NVL019', 4);

-- Tủ hồ sơ
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP006', 'NVL001', 5),
('TP006', 'NVL002', 2),
('TP006', 'NVL017', 4),
('TP006', 'NVL018', 3);

-- Kệ sách
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP007', 'NVL001', 3),
('TP007', 'NVL007', 2),
('TP007', 'NVL013', 1),
('TP007', 'NVL015', 12);

-- Kệ tivi
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP008', 'NVL001', 4),
('TP008', 'NVL008', 1),
('TP008', 'NVL015', 16),
('TP008', 'NVL012', 1);

-- Giường ngủ
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP009', 'NVL004', 8),
('TP009', 'NVL006', 2),
('TP009', 'NVL015', 24),
('TP009', 'NVL010', 2);

-- Bàn trang điểm
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP010', 'NVL001', 3),
('TP010', 'NVL008', 1),
('TP010', 'NVL018', 2),
('TP010', 'NVL019', 2);

-- Ván lót gỗ
INSERT INTO CHITIETTHANHPHAM
(maTP, maNVL, soLuong) VALUES
('TP011', 'NVL001', 2),
('TP011', 'NVL025', 6);


-- ==========================================================
-- 18.1 NGUYÊN VẬT LIỆU BỔ SUNG
-- ==========================================================

INSERT INTO NGUYENVATLIEU
(maNVL, tenNVL, maDVT, soLuong, isDeleted, trangThai) VALUES
('NVL026', 'Gỗ óc chó',          5, 180, 0, 1),
('NVL027', 'Gỗ tần bì',          5, 220, 0, 1),
('NVL028', 'Gỗ xoan đào',        5, 160, 0, 1),
('NVL029', 'Ván HDF 12mm',       5, 240, 0, 1),
('NVL030', 'Ván nhựa PVC',       5, 130, 0, 1),
('NVL031', 'Sơn PU màu óc chó',  2, 90,  0, 1),
('NVL032', 'Sơn lót gỗ',         2, 110, 0, 1),
('NVL033', 'Keo ghép gỗ',        2, 75,  0, 1),
('NVL034', 'Bản lề giảm chấn',   3, 500, 0, 1),
('NVL035', 'Thanh treo tủ',      4, 240, 0, 1),
('NVL036', 'Núm tay nắm gỗ',     3, 350, 0, 1),
('NVL037', 'Đèn LED tủ',         3, 180, 0, 1);


-- ==========================================================
-- 18.2 THÀNH PHẨM BỔ SUNG
-- ==========================================================

INSERT INTO THANHPHAM
(maTP, tenTP, donViTinh, soLuong, isDeleted, trangThai) VALUES
('TP012', 'Bàn trà gỗ óc chó',       'Cái', 18, 0, 1),
('TP013', 'Tủ giày gỗ công nghiệp',  'Cái', 22, 0, 1),
('TP014', 'Tủ bếp treo tường',       'Cái', 16, 0, 1),
('TP015', 'Ghế thư giãn gỗ',         'Cái', 20, 0, 1),
('TP016', 'Kệ góc trang trí',        'Cái', 25, 0, 1),
('TP017', 'Bàn console gỗ',          'Cái', 12, 0, 1);


-- ==========================================================
-- 18.3 ĐỊNH MỨC CHO THÀNH PHẨM BỔ SUNG
-- ==========================================================

-- Bàn trà gỗ óc chó
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP012', 'NVL026', 3),
('TP012', 'NVL033', 1),
('TP012', 'NVL031', 1),
('TP012', 'NVL015', 8);

-- Tủ giày gỗ công nghiệp
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP013', 'NVL029', 4),
('TP013', 'NVL030', 1),
('TP013', 'NVL034', 4),
('TP013', 'NVL036', 2);

-- Tủ bếp treo tường
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP014', 'NVL027', 5),
('TP014', 'NVL029', 2),
('TP014', 'NVL034', 6),
('TP014', 'NVL035', 2);

-- Ghế thư giãn gỗ
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP015', 'NVL028', 3),
('TP015', 'NVL022', 2),
('TP015', 'NVL023', 2),
('TP015', 'NVL031', 1);

-- Kệ góc trang trí
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP016', 'NVL030', 2),
('TP016', 'NVL033', 1),
('TP016', 'NVL036', 2),
('TP016', 'NVL037', 1);

-- Bàn console gỗ
INSERT INTO CHITIETTHANHPHAM (maTP, maNVL, soLuong) VALUES
('TP017', 'NVL027', 3),
('TP017', 'NVL020', 4),
('TP017', 'NVL032', 1),
('TP017', 'NVL015', 10);


-- ==========================================================
-- 18.4 NGUYÊN VẬT LIỆU TẠI XƯỞNG
-- Tồn kho thực tế tại xưởng, tách biệt với kho nguyên vật liệu.
-- ==========================================================

INSERT INTO NGUYENVATLIEUXUONG
(maNVL, tenNVL, maDVT, soLuong) VALUES
('NVL001', 'Gỗ MDF 18mm',        5, 95),
('NVL003', 'Gỗ cao su',          5, 70),
('NVL005', 'Gỗ sồi',             5, 55),
('NVL007', 'Veneer gỗ sồi',      4, 35),
('NVL009', 'Sơn PU trắng',       2, 28),
('NVL010', 'Sơn PU nâu',         2, 34),
('NVL013', 'Keo dán gỗ',         2, 22),
('NVL015', 'Vít gỗ 4x40',        3, 650),
('NVL017', 'Bản lề tủ',          3, 85),
('NVL018', 'Ray trượt ngăn kéo', 3, 60),
('NVL020', 'Chân bàn',           3, 45),
('NVL021', 'Chân ghế',           3, 80),
('NVL022', 'Đệm mút',            5, 48),
('NVL023', 'Vải bọc ghế',         4, 65),
('NVL026', 'Gỗ óc chó',          5, 42),
('NVL027', 'Gỗ tần bì',          5, 58),
('NVL028', 'Gỗ xoan đào',        5, 36),
('NVL029', 'Ván HDF 12mm',       5, 75),
('NVL030', 'Ván nhựa PVC',       5, 40),
('NVL031', 'Sơn PU màu óc chó',  2, 25),
('NVL032', 'Sơn lót gỗ',         2, 30),
('NVL033', 'Keo ghép gỗ',        2, 18),
('NVL034', 'Bản lề giảm chấn',   3, 140),
('NVL035', 'Thanh treo tủ',      4, 70),
('NVL036', 'Núm tay nắm gỗ',     3, 110),
('NVL037', 'Đèn LED tủ',         3, 55);

-- ==========================================================
-- 19. PHIẾU NHẬP KHO
-- ==========================================================

INSERT INTO PHIEUNHAPKHO
(maPN, ngayNhap, ghiChu) VALUES

('PN001', '2026-10-09 08:00:00',
 'Nhập bổ sung gỗ MDF và vít gỗ từ nhà cung cấp mẫu');


-- ==========================================================
-- 20. CHI TIẾT PHIẾU NHẬP
-- ==========================================================

INSERT INTO CHITIETPHIEUNHAP
(maPN, maNVL, soLuong) VALUES

('PN001', 'NVL001', 50),
('PN001', 'NVL015', 200);


-- ==========================================================
-- 21. PHIẾU XUẤT NGUYÊN VẬT LIỆU
-- ==========================================================

INSERT INTO PHIEUXUATNVL
(maPX, ngayXuat, trangThai, maNV, maQL, ngayDuyet, ghiChu, loaiPhieu) VALUES

('PXN001', '2026-09-10 08:00:00', 1,
 'NV001', 'QL001', '2026-09-10 10:00:00', 'Cấp vật tư cho lệnh sản xuất YC001', 0),

('PXN002', '2026-09-12 09:00:00', 1,
 'NV002', 'QL002', '2026-09-12 11:00:00', 'Cấp vật tư cho lệnh sản xuất YC002', 0),

('PXN003', '2026-09-18 08:30:00', 0,
 'NV001', NULL, NULL, 'Đề nghị cấp vật tư cho lệnh sản xuất YC003', 0),

('PXN004', '2026-09-20 10:00:00', 1,
 'NV002', 'QL001', '2026-09-20 12:00:00', 'Cấp vật tư cho lệnh sản xuất YC004', 0);


-- ==========================================================
-- 22. CHI TIẾT PHIẾU XUẤT NVL
-- ==========================================================

INSERT INTO CHITIETPHIEUXUATNVL
(maPX, maNVL, soLuong) VALUES

-- PXN001
('PXN001', 'NVL001', 20),
('PXN001', 'NVL003', 10),
('PXN001', 'NVL015', 100),
('PXN001', 'NVL020', 20),

-- PXN002
('PXN002', 'NVL005', 30),
('PXN002', 'NVL015', 100),
('PXN002', 'NVL010', 10),
('PXN002', 'NVL021', 20),

-- PXN003 - Chưa duyệt
('PXN003', 'NVL001', 20),
('PXN003', 'NVL002', 15),
('PXN003', 'NVL017', 10),
('PXN003', 'NVL019', 10),

-- PXN004
('PXN004', 'NVL004', 40),
('PXN004', 'NVL006', 10),
('PXN004', 'NVL015', 100),
('PXN004', 'NVL010', 10);


-- ==========================================================
-- 23. PHIẾU XUẤT THÀNH PHẨM
-- ==========================================================

INSERT INTO PHIEUXUATTP
(maPX, ngayXuat, trangThai, maNV, maQL, loaiPhieu) VALUES

('PXT001', '2026-09-11 14:00:00', 1,
 'NV001', 'QL001', 1),

('PXT002', '2026-09-16 15:00:00', 1,
 'NV002', 'QL002', 1),

('PXT003', '2026-09-21 09:00:00', 0,
 'NV001', NULL, 1);


-- ==========================================================
-- 24. CHI TIẾT PHIẾU XUẤT THÀNH PHẨM
-- ==========================================================

INSERT INTO CHITIETPHIEUXUATTP
(maPX, maTP, soLuong) VALUES

-- PXT001
('PXT001', 'TP001', 5),
('PXT001', 'TP003', 10),

-- PXT002
('PXT002', 'TP005', 3),
('PXT002', 'TP007', 5),

-- PXT003 - Chưa duyệt
('PXT003', 'TP002', 3),
('PXT003', 'TP009', 2);


-- ==========================================================
-- 25. YÊU CẦU SẢN XUẤT
-- ==========================================================

INSERT INTO YEUCAU
(maYC, ngayYC, maNV, trangThai) VALUES

('YC001', '2026-09-05 08:00:00', 'QL001', 1),
('YC002', '2026-09-09 09:00:00', 'QL002', 1),
('YC003', '2026-09-14 10:00:00', 'QL001', 2),
('YC004', '2026-09-19 08:30:00', 'QL002', 2);


-- ==========================================================
-- 26. CHI TIẾT YÊU CẦU SẢN XUẤT
-- ==========================================================

INSERT INTO CHITIETYEUCAU
(maYC, maTP, soLuong) VALUES

-- YC001
('YC001', 'TP001', 20),
('YC001', 'TP003', 15),

-- YC002
('YC002', 'TP005', 10),
('YC002', 'TP006', 8),

-- YC003
('YC003', 'TP007', 15),
('YC003', 'TP008', 10),

-- YC004
('YC004', 'TP009', 5),
('YC004', 'TP010', 8);


-- ==========================================================
-- 27. DỮ LIỆU LỊCH SỬ BỔ SUNG (2024 - 2026)
-- Dùng để kiểm thử thống kê theo tháng/năm và báo cáo tồn kho.
-- Các phiếu được phân bổ đều trong hơn 2 năm, gồm cả trạng thái
-- đã duyệt, chờ duyệt và từ chối.
-- ==========================================================

-- 27.1 PHIẾU NHẬP KHO LỊCH SỬ
INSERT INTO PHIEUNHAPKHO (maPN, ngayNhap, ghiChu) VALUES
('PN002', '2024-01-15 08:15:00', 'Nhập gỗ MDF và phụ kiện đầu năm'),
('PN003', '2024-04-08 09:00:00', 'Nhập gỗ cao su, gỗ thông và sơn'),
('PN004', '2024-07-22 08:30:00', 'Nhập phụ kiện tủ và bàn'),
('PN005', '2024-11-05 10:00:00', 'Nhập nguyên liệu phục vụ đơn hàng cuối năm'),
('PN006', '2025-02-14 08:00:00', 'Nhập gỗ MDF và vật tư lắp ráp'),
('PN007', '2025-05-19 09:30:00', 'Nhập gỗ sồi, veneer và laminate'),
('PN008', '2025-08-27 08:45:00', 'Nhập sơn, keo và phụ kiện'),
('PN009', '2025-12-12 10:15:00', 'Nhập bổ sung vật tư mùa cao điểm'),
('PN010', '2026-01-20 08:00:00', 'Nhập nguyên liệu đầu năm 2026'),
('PN011', '2026-03-16 09:15:00', 'Nhập gỗ và vật tư hoàn thiện'),
('PN012', '2026-06-25 08:30:00', 'Nhập phụ kiện nội thất'),
('PN013', '2026-08-18 10:00:00', 'Nhập vật tư cho kế hoạch quý 3');

INSERT INTO CHITIETPHIEUNHAP (maPN, maNVL, soLuong) VALUES
('PN002', 'NVL001', 180), ('PN002', 'NVL015', 600), ('PN002', 'NVL013', 40),
('PN003', 'NVL003', 150), ('PN003', 'NVL004', 120), ('PN003', 'NVL010', 35),
('PN004', 'NVL017', 180), ('PN004', 'NVL018', 120), ('PN004', 'NVL019', 200),
('PN005', 'NVL002', 140), ('PN005', 'NVL005', 100), ('PN005', 'NVL021', 160),
('PN006', 'NVL001', 200), ('PN006', 'NVL006', 120), ('PN006', 'NVL016', 500),
('PN007', 'NVL005', 160), ('PN007', 'NVL007', 100), ('PN007', 'NVL008', 120),
('PN008', 'NVL009', 45), ('PN008', 'NVL012', 35), ('PN008', 'NVL014', 30),
('PN009', 'NVL015', 700), ('PN009', 'NVL017', 220), ('PN009', 'NVL020', 100),
('PN010', 'NVL003', 180), ('PN010', 'NVL004', 140), ('PN010', 'NVL022', 100),
('PN011', 'NVL001', 220), ('PN011', 'NVL013', 50), ('PN011', 'NVL024', 80),
('PN012', 'NVL018', 160), ('PN012', 'NVL019', 240), ('PN012', 'NVL025', 500),
('PN013', 'NVL002', 180), ('PN013', 'NVL010', 40), ('PN013', 'NVL023', 120);

-- 27.2 PHIẾU XUẤT NGUYÊN VẬT LIỆU LỊCH SỬ
INSERT INTO PHIEUXUATNVL
(maPX, ngayXuat, trangThai, maNV, maQL, ngayDuyet, ghiChu, loaiPhieu) VALUES
('PXN005', '2024-02-02 08:00:00', 1, 'NV001', 'QL001', '2024-02-02 10:00:00', 'Cấp vật tư sản xuất đợt 1', 0),
('PXN006', '2024-05-10 08:30:00', 1, 'NV002', 'QL002', '2024-05-10 11:00:00', 'Cấp vật tư sản xuất đợt 2', 0),
('PXN007', '2024-08-16 09:00:00', 1, 'NV005', 'QL003', '2024-08-16 11:30:00', 'Cấp vật tư sản xuất đợt 3', 0),
('PXN008', '2024-12-03 08:15:00', 1, 'NV006', 'QL003', '2024-12-03 10:15:00', 'Cấp vật tư đơn hàng cuối năm', 0),
('PXN009', '2025-03-07 09:00:00', 1, 'NV005', 'QL004', '2025-03-07 11:00:00', 'Cấp vật tư sản xuất quý 1', 0),
('PXN010', '2025-06-13 08:30:00', 1, 'NV006', 'QL003', '2025-06-13 10:30:00', 'Cấp vật tư sản xuất quý 2', 0),
('PXN011', '2025-09-19 09:15:00', 0, 'NV007', NULL, NULL, 'Chờ xử lý do vượt định mức tồn kho', 0),
('PXN012', '2025-12-22 08:00:00', 1, 'NV008', 'QL003', '2025-12-22 10:00:00', 'Cấp vật tư cao điểm cuối năm', 0),
('PXN013', '2026-02-06 09:00:00', 1, 'NV005', 'QL004', '2026-02-06 11:00:00', 'Cấp vật tư kế hoạch tháng 2', 0),
('PXN014', '2026-04-17 08:30:00', 1, 'NV006', 'QL003', '2026-04-17 10:30:00', 'Cấp vật tư kế hoạch tháng 4', 0),
('PXN015', '2026-07-03 09:00:00', 1, 'NV007', 'QL001', '2026-07-03 11:00:00', 'Cấp vật tư kế hoạch quý 3', 0),
('PXN016', '2026-08-29 08:15:00', 0, 'NV008', NULL, NULL, 'Chờ duyệt cấp vật tư bổ sung', 0);

INSERT INTO CHITIETPHIEUXUATNVL (maPX, maNVL, soLuong) VALUES
('PXN005', 'NVL001', 45), ('PXN005', 'NVL003', 25), ('PXN005', 'NVL015', 180),
('PXN006', 'NVL004', 35), ('PXN006', 'NVL010', 12), ('PXN006', 'NVL021', 40),
('PXN007', 'NVL005', 30), ('PXN007', 'NVL017', 24), ('PXN007', 'NVL019', 36),
('PXN008', 'NVL001', 60), ('PXN008', 'NVL002', 25), ('PXN008', 'NVL018', 30),
('PXN009', 'NVL003', 50), ('PXN009', 'NVL006', 20), ('PXN009', 'NVL015', 240),
('PXN010', 'NVL007', 25), ('PXN010', 'NVL008', 18), ('PXN010', 'NVL013', 20),
('PXN011', 'NVL001', 100), ('PXN011', 'NVL015', 300),
('PXN012', 'NVL004', 55), ('PXN012', 'NVL010', 16), ('PXN012', 'NVL020', 28),
('PXN013', 'NVL001', 70), ('PXN013', 'NVL002', 30), ('PXN013', 'NVL017', 40),
('PXN014', 'NVL003', 65), ('PXN014', 'NVL005', 35), ('PXN014', 'NVL021', 50),
('PXN015', 'NVL001', 80), ('PXN015', 'NVL008', 25), ('PXN015', 'NVL019', 45),
('PXN016', 'NVL002', 40), ('PXN016', 'NVL015', 120);

-- 27.3 PHIẾU XUẤT THÀNH PHẨM LỊCH SỬ
INSERT INTO PHIEUXUATTP
(maPX, ngayXuat, trangThai, maNV, maQL, loaiPhieu, ngayDuyet, lyDoTuChoi) VALUES
('PXT004', '2024-02-20 14:00:00', 1, 'NV001', 'QL001', 1, '2024-02-20 15:00:00', NULL),
('PXT005', '2024-06-05 15:00:00', 1, 'NV002', 'QL002', 1, '2024-06-05 16:00:00', NULL),
('PXT006', '2024-10-18 09:00:00', 1, 'NV005', 'QL003', 1, '2024-10-18 10:00:00', NULL),
('PXT007', '2025-01-24 14:30:00', 1, 'NV006', 'QL004', 1, '2025-01-24 15:30:00', NULL),
('PXT008', '2025-04-11 10:00:00', 1, 'NV007', 'QL003', 1, '2025-04-11 11:00:00', NULL),
('PXT009', '2025-07-25 14:00:00', 2, 'NV008', 'QL004', 1, '2025-07-25 15:00:00', 'Số lượng xuất vượt số lượng thành phẩm khả dụng'),
('PXT010', '2025-11-14 09:30:00', 1, 'NV005', 'QL005', 1, '2025-11-14 10:30:00', NULL),
('PXT011', '2026-02-27 15:00:00', 1, 'NV006', 'QL003', 1, '2026-02-27 16:00:00', NULL),
('PXT012', '2026-05-08 10:00:00', 1, 'NV007', 'QL001', 1, '2026-05-08 11:00:00', NULL),
('PXT013', '2026-08-12 14:00:00', 0, 'NV008', NULL, 1, NULL, NULL);

INSERT INTO CHITIETPHIEUXUATTP (maPX, maTP, soLuong) VALUES
('PXT004', 'TP001', 8), ('PXT004', 'TP003', 12),
('PXT005', 'TP002', 6), ('PXT005', 'TP007', 8),
('PXT006', 'TP005', 4), ('PXT006', 'TP006', 5),
('PXT007', 'TP004', 10), ('PXT007', 'TP010', 5),
('PXT008', 'TP001', 7), ('PXT008', 'TP008', 6),
('PXT009', 'TP009', 20),
('PXT010', 'TP003', 15), ('PXT010', 'TP005', 3),
('PXT011', 'TP006', 8), ('PXT011', 'TP007', 10),
('PXT012', 'TP002', 9), ('PXT012', 'TP004', 12),
('PXT013', 'TP009', 4), ('PXT013', 'TP010', 3);

-- 27.4 LỆNH SẢN XUẤT LỊCH SỬ
INSERT INTO YEUCAU (maYC, ngayYC, maNV, trangThai) VALUES
('YC005', '2024-01-08 08:00:00', 'QL001', 2),
('YC006', '2024-04-02 09:00:00', 'QL002', 2),
('YC007', '2024-07-01 08:30:00', 'QL003', 2),
('YC008', '2024-10-07 10:00:00', 'QL004', 2),
('YC009', '2025-01-06 08:00:00', 'QL005', 2),
('YC010', '2025-04-04 09:30:00', 'QL003', 2),
('YC011', '2025-07-07 08:00:00', 'QL004', 1),
('YC012', '2025-10-06 09:00:00', 'QL005', 2),
('YC013', '2026-01-05 08:00:00', 'QL003', 2),
('YC014', '2026-03-02 09:30:00', 'QL004', 2),
('YC015', '2026-06-01 08:00:00', 'QL005', 1),
('YC016', '2026-08-03 09:00:00', 'QL003', 0);

INSERT INTO CHITIETYEUCAU (maYC, maTP, soLuong) VALUES
('YC005', 'TP001', 18), ('YC005', 'TP003', 24),
('YC006', 'TP002', 12), ('YC006', 'TP005', 6),
('YC007', 'TP006', 10), ('YC007', 'TP007', 15),
('YC008', 'TP008', 12), ('YC008', 'TP010', 8),
('YC009', 'TP003', 30), ('YC009', 'TP004', 18),
('YC010', 'TP001', 20), ('YC010', 'TP009', 6),
('YC011', 'TP005', 8), ('YC011', 'TP006', 12),
('YC012', 'TP007', 20), ('YC012', 'TP008', 15),
('YC013', 'TP002', 16), ('YC013', 'TP003', 28),
('YC014', 'TP004', 22), ('YC014', 'TP010', 10),
('YC015', 'TP001', 25), ('YC015', 'TP005', 9),
('YC016', 'TP009', 7), ('YC016', 'TP011', 20);