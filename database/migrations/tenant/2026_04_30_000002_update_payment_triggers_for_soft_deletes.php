<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing triggers
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_update');
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_delete');

        // Trigger for INSERT payment
        DB::unprepared('
            CREATE TRIGGER after_payment_insert
            AFTER INSERT ON payments
            FOR EACH ROW
            BEGIN
                DECLARE bulan INT;
                DECLARE tahun_pembayaran VARCHAR(4);
                DECLARE id_debit BIGINT;
                DECLARE id_kredit BIGINT;
                DECLARE kode_debit_clean VARCHAR(50);
                DECLARE kode_kredit_clean VARCHAR(50);
                
                -- Only process if not soft-deleted (though unlikely on insert)
                IF NEW.deleted_at IS NULL THEN
                    SET bulan = MONTH(NEW.tanggal_pembayaran);
                    SET tahun_pembayaran = YEAR(NEW.tanggal_pembayaran);
                    
                    SET kode_debit_clean = REPLACE(NEW.rekening_debit, ".", "");
                    SET kode_kredit_clean = REPLACE(NEW.rekening_kredit, ".", "");
                    
                    SET id_debit = CAST(CONCAT(kode_debit_clean, tahun_pembayaran, NEW.business_id) AS UNSIGNED);
                    SET id_kredit = CAST(CONCAT(kode_kredit_clean, tahun_pembayaran, NEW.business_id) AS UNSIGNED);
                    
                    INSERT INTO balances (
                        id, business_id, kode_akun, tahun,
                        debit_01, kredit_01, debit_02, kredit_02, debit_03, kredit_03,
                        debit_04, kredit_04, debit_05, kredit_05, debit_06, kredit_06,
                        debit_07, kredit_07, debit_08, kredit_08, debit_09, kredit_09,
                        debit_10, kredit_10, debit_11, kredit_11, debit_12, kredit_12,
                        created_at, updated_at
                    ) VALUES (
                        id_debit, NEW.business_id, NEW.rekening_debit, tahun_pembayaran,
                        CASE WHEN bulan = 1 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 2 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 3 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 4 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 5 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 6 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 7 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 8 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 9 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 10 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 11 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan = 12 THEN NEW.total_harga ELSE 0 END, 0,
                        NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        debit_01 = CASE WHEN bulan = 1 THEN debit_01 + NEW.total_harga ELSE debit_01 END,
                        debit_02 = CASE WHEN bulan = 2 THEN debit_02 + NEW.total_harga ELSE debit_02 END,
                        debit_03 = CASE WHEN bulan = 3 THEN debit_03 + NEW.total_harga ELSE debit_03 END,
                        debit_04 = CASE WHEN bulan = 4 THEN debit_04 + NEW.total_harga ELSE debit_04 END,
                        debit_05 = CASE WHEN bulan = 5 THEN debit_05 + NEW.total_harga ELSE debit_05 END,
                        debit_06 = CASE WHEN bulan = 6 THEN debit_06 + NEW.total_harga ELSE debit_06 END,
                        debit_07 = CASE WHEN bulan = 7 THEN debit_07 + NEW.total_harga ELSE debit_07 END,
                        debit_08 = CASE WHEN bulan = 8 THEN debit_08 + NEW.total_harga ELSE debit_08 END,
                        debit_09 = CASE WHEN bulan = 9 THEN debit_09 + NEW.total_harga ELSE debit_09 END,
                        debit_10 = CASE WHEN bulan = 10 THEN debit_10 + NEW.total_harga ELSE debit_10 END,
                        debit_11 = CASE WHEN bulan = 11 THEN debit_11 + NEW.total_harga ELSE debit_11 END,
                        debit_12 = CASE WHEN bulan = 12 THEN debit_12 + NEW.total_harga ELSE debit_12 END,
                        updated_at = NOW();
                    
                    INSERT INTO balances (
                        id, business_id, kode_akun, tahun,
                        debit_01, kredit_01, debit_02, kredit_02, debit_03, kredit_03,
                        debit_04, kredit_04, debit_05, kredit_05, debit_06, kredit_06,
                        debit_07, kredit_07, debit_08, kredit_08, debit_09, kredit_09,
                        debit_10, kredit_10, debit_11, kredit_11, debit_12, kredit_12,
                        created_at, updated_at
                    ) VALUES (
                        id_kredit, NEW.business_id, NEW.rekening_kredit, tahun_pembayaran,
                        0, CASE WHEN bulan = 1 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 2 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 3 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 4 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 5 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 6 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 7 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 8 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 9 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 10 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 11 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan = 12 THEN NEW.total_harga ELSE 0 END,
                        NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        kredit_01 = CASE WHEN bulan = 1 THEN kredit_01 + NEW.total_harga ELSE kredit_01 END,
                        kredit_02 = CASE WHEN bulan = 2 THEN kredit_02 + NEW.total_harga ELSE kredit_02 END,
                        kredit_03 = CASE WHEN bulan = 3 THEN kredit_03 + NEW.total_harga ELSE kredit_03 END,
                        kredit_04 = CASE WHEN bulan = 4 THEN kredit_04 + NEW.total_harga ELSE kredit_04 END,
                        kredit_05 = CASE WHEN bulan = 5 THEN kredit_05 + NEW.total_harga ELSE kredit_05 END,
                        kredit_06 = CASE WHEN bulan = 6 THEN kredit_06 + NEW.total_harga ELSE kredit_06 END,
                        kredit_07 = CASE WHEN bulan = 7 THEN kredit_07 + NEW.total_harga ELSE kredit_07 END,
                        kredit_08 = CASE WHEN bulan = 8 THEN kredit_08 + NEW.total_harga ELSE kredit_08 END,
                        kredit_09 = CASE WHEN bulan = 9 THEN kredit_09 + NEW.total_harga ELSE kredit_09 END,
                        kredit_10 = CASE WHEN bulan = 10 THEN kredit_10 + NEW.total_harga ELSE kredit_10 END,
                        kredit_11 = CASE WHEN bulan = 11 THEN kredit_11 + NEW.total_harga ELSE kredit_11 END,
                        kredit_12 = CASE WHEN bulan = 12 THEN kredit_12 + NEW.total_harga ELSE kredit_12 END,
                        updated_at = NOW();
                END IF;
            END
        ');

        // Trigger for UPDATE payment
        DB::unprepared('
            CREATE TRIGGER after_payment_update
            AFTER UPDATE ON payments
            FOR EACH ROW
            BEGIN
                DECLARE bulan_old INT;
                DECLARE tahun_old VARCHAR(4);
                DECLARE bulan_new INT;
                DECLARE tahun_new VARCHAR(4);
                DECLARE id_debit_old BIGINT;
                DECLARE id_kredit_old BIGINT;
                DECLARE id_debit_new BIGINT;
                DECLARE id_kredit_new BIGINT;
                DECLARE kode_debit_old_clean VARCHAR(50);
                DECLARE kode_kredit_old_clean VARCHAR(50);
                DECLARE kode_debit_new_clean VARCHAR(50);
                DECLARE kode_kredit_new_clean VARCHAR(50);
                
                -- Reverse OLD values ONLY IF it was NOT soft-deleted
                IF OLD.deleted_at IS NULL THEN
                    SET bulan_old = MONTH(OLD.tanggal_pembayaran);
                    SET tahun_old = YEAR(OLD.tanggal_pembayaran);
                    SET kode_debit_old_clean = REPLACE(OLD.rekening_debit, ".", "");
                    SET kode_kredit_old_clean = REPLACE(OLD.rekening_kredit, ".", "");
                    SET id_debit_old = CAST(CONCAT(kode_debit_old_clean, tahun_old, OLD.business_id) AS UNSIGNED);
                    SET id_kredit_old = CAST(CONCAT(kode_kredit_old_clean, tahun_old, OLD.business_id) AS UNSIGNED);
                    
                    UPDATE balances SET
                        debit_01 = CASE WHEN bulan_old = 1 THEN debit_01 - OLD.total_harga ELSE debit_01 END,
                        debit_02 = CASE WHEN bulan_old = 2 THEN debit_02 - OLD.total_harga ELSE debit_02 END,
                        debit_03 = CASE WHEN bulan_old = 3 THEN debit_03 - OLD.total_harga ELSE debit_03 END,
                        debit_04 = CASE WHEN bulan_old = 4 THEN debit_04 - OLD.total_harga ELSE debit_04 END,
                        debit_05 = CASE WHEN bulan_old = 5 THEN debit_05 - OLD.total_harga ELSE debit_05 END,
                        debit_06 = CASE WHEN bulan_old = 6 THEN debit_06 - OLD.total_harga ELSE debit_06 END,
                        debit_07 = CASE WHEN bulan_old = 7 THEN debit_07 - OLD.total_harga ELSE debit_07 END,
                        debit_08 = CASE WHEN bulan_old = 8 THEN debit_08 - OLD.total_harga ELSE debit_08 END,
                        debit_09 = CASE WHEN bulan_old = 9 THEN debit_09 - OLD.total_harga ELSE debit_09 END,
                        debit_10 = CASE WHEN bulan_old = 10 THEN debit_10 - OLD.total_harga ELSE debit_10 END,
                        debit_11 = CASE WHEN bulan_old = 11 THEN debit_11 - OLD.total_harga ELSE debit_11 END,
                        debit_12 = CASE WHEN bulan_old = 12 THEN debit_12 - OLD.total_harga ELSE debit_12 END,
                        updated_at = NOW()
                    WHERE id = id_debit_old;
                    
                    UPDATE balances SET
                        kredit_01 = CASE WHEN bulan_old = 1 THEN kredit_01 - OLD.total_harga ELSE kredit_01 END,
                        kredit_02 = CASE WHEN bulan_old = 2 THEN kredit_02 - OLD.total_harga ELSE kredit_02 END,
                        kredit_03 = CASE WHEN bulan_old = 3 THEN kredit_03 - OLD.total_harga ELSE kredit_03 END,
                        kredit_04 = CASE WHEN bulan_old = 4 THEN kredit_04 - OLD.total_harga ELSE kredit_04 END,
                        kredit_05 = CASE WHEN bulan_old = 5 THEN kredit_05 - OLD.total_harga ELSE kredit_05 END,
                        kredit_06 = CASE WHEN bulan_old = 6 THEN kredit_06 - OLD.total_harga ELSE kredit_06 END,
                        kredit_07 = CASE WHEN bulan_old = 7 THEN kredit_07 - OLD.total_harga ELSE kredit_07 END,
                        kredit_08 = CASE WHEN bulan_old = 8 THEN kredit_08 - OLD.total_harga ELSE kredit_08 END,
                        kredit_09 = CASE WHEN bulan_old = 9 THEN kredit_09 - OLD.total_harga ELSE kredit_09 END,
                        kredit_10 = CASE WHEN bulan_old = 10 THEN kredit_10 - OLD.total_harga ELSE kredit_10 END,
                        kredit_11 = CASE WHEN bulan_old = 11 THEN kredit_11 - OLD.total_harga ELSE kredit_11 END,
                        kredit_12 = CASE WHEN bulan_old = 12 THEN kredit_12 - OLD.total_harga ELSE kredit_12 END,
                        updated_at = NOW()
                    WHERE id = id_kredit_old;
                END IF;
                
                -- Add NEW values ONLY IF it is NOT soft-deleted
                IF NEW.deleted_at IS NULL THEN
                    SET bulan_new = MONTH(NEW.tanggal_pembayaran);
                    SET tahun_new = YEAR(NEW.tanggal_pembayaran);
                    SET kode_debit_new_clean = REPLACE(NEW.rekening_debit, ".", "");
                    SET kode_kredit_new_clean = REPLACE(NEW.rekening_kredit, ".", "");
                    SET id_debit_new = CAST(CONCAT(kode_debit_new_clean, tahun_new, NEW.business_id) AS UNSIGNED);
                    SET id_kredit_new = CAST(CONCAT(kode_kredit_new_clean, tahun_new, NEW.business_id) AS UNSIGNED);
                    
                    INSERT INTO balances (
                        id, business_id, kode_akun, tahun,
                        debit_01, kredit_01, debit_02, kredit_02, debit_03, kredit_03,
                        debit_04, kredit_04, debit_05, kredit_05, debit_06, kredit_06,
                        debit_07, kredit_07, debit_08, kredit_08, debit_09, kredit_09,
                        debit_10, kredit_10, debit_11, kredit_11, debit_12, kredit_12,
                        created_at, updated_at
                    ) VALUES (
                        id_debit_new, NEW.business_id, NEW.rekening_debit, tahun_new,
                        CASE WHEN bulan_new = 1 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 2 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 3 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 4 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 5 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 6 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 7 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 8 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 9 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 10 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 11 THEN NEW.total_harga ELSE 0 END, 0,
                        CASE WHEN bulan_new = 12 THEN NEW.total_harga ELSE 0 END, 0,
                        NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        debit_01 = CASE WHEN bulan_new = 1 THEN debit_01 + NEW.total_harga ELSE debit_01 END,
                        debit_02 = CASE WHEN bulan_new = 2 THEN debit_02 + NEW.total_harga ELSE debit_02 END,
                        debit_03 = CASE WHEN bulan_new = 3 THEN debit_03 + NEW.total_harga ELSE debit_03 END,
                        debit_04 = CASE WHEN bulan_new = 4 THEN debit_04 + NEW.total_harga ELSE debit_04 END,
                        debit_05 = CASE WHEN bulan_new = 5 THEN debit_05 + NEW.total_harga ELSE debit_05 END,
                        debit_06 = CASE WHEN bulan_new = 6 THEN debit_06 + NEW.total_harga ELSE debit_06 END,
                        debit_07 = CASE WHEN bulan_new = 7 THEN debit_07 + NEW.total_harga ELSE debit_07 END,
                        debit_08 = CASE WHEN bulan_new = 8 THEN debit_08 + NEW.total_harga ELSE debit_08 END,
                        debit_09 = CASE WHEN bulan_new = 9 THEN debit_09 + NEW.total_harga ELSE debit_09 END,
                        debit_10 = CASE WHEN bulan_new = 10 THEN debit_10 + NEW.total_harga ELSE debit_10 END,
                        debit_11 = CASE WHEN bulan_new = 11 THEN debit_11 + NEW.total_harga ELSE debit_11 END,
                        debit_12 = CASE WHEN bulan_new = 12 THEN debit_12 + NEW.total_harga ELSE debit_12 END,
                        updated_at = NOW();
                    
                    INSERT INTO balances (
                        id, business_id, kode_akun, tahun,
                        debit_01, kredit_01, debit_02, kredit_02, debit_03, kredit_03,
                        debit_04, kredit_04, debit_05, kredit_05, debit_06, kredit_06,
                        debit_07, kredit_07, debit_08, kredit_08, debit_09, kredit_09,
                        debit_10, kredit_10, debit_11, kredit_11, debit_12, kredit_12,
                        created_at, updated_at
                    ) VALUES (
                        id_kredit_new, NEW.business_id, NEW.rekening_kredit, tahun_new,
                        0, CASE WHEN bulan_new = 1 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 2 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 3 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 4 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 5 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 6 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 7 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 8 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 9 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 10 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 11 THEN NEW.total_harga ELSE 0 END,
                        0, CASE WHEN bulan_new = 12 THEN NEW.total_harga ELSE 0 END,
                        NOW(), NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        kredit_01 = CASE WHEN bulan_new = 1 THEN kredit_01 + NEW.total_harga ELSE kredit_01 END,
                        kredit_02 = CASE WHEN bulan_new = 2 THEN kredit_02 + NEW.total_harga ELSE kredit_02 END,
                        kredit_03 = CASE WHEN bulan_new = 3 THEN kredit_03 + NEW.total_harga ELSE kredit_03 END,
                        kredit_04 = CASE WHEN bulan_new = 4 THEN kredit_04 + NEW.total_harga ELSE kredit_04 END,
                        kredit_05 = CASE WHEN bulan_new = 5 THEN kredit_05 + NEW.total_harga ELSE kredit_05 END,
                        kredit_06 = CASE WHEN bulan_new = 6 THEN kredit_06 + NEW.total_harga ELSE kredit_06 END,
                        kredit_07 = CASE WHEN bulan_new = 7 THEN kredit_07 + NEW.total_harga ELSE kredit_07 END,
                        kredit_08 = CASE WHEN bulan_new = 8 THEN kredit_08 + NEW.total_harga ELSE kredit_08 END,
                        kredit_09 = CASE WHEN bulan_new = 9 THEN kredit_09 + NEW.total_harga ELSE kredit_09 END,
                        kredit_10 = CASE WHEN bulan_new = 10 THEN kredit_10 + NEW.total_harga ELSE kredit_10 END,
                        kredit_11 = CASE WHEN bulan_new = 11 THEN kredit_11 + NEW.total_harga ELSE kredit_11 END,
                        kredit_12 = CASE WHEN bulan_new = 12 THEN kredit_12 + NEW.total_harga ELSE kredit_12 END,
                        updated_at = NOW();
                END IF;
            END
        ');

        // Trigger for DELETE payment (Hard Delete)
        DB::unprepared('
            CREATE TRIGGER after_payment_delete
            AFTER DELETE ON payments
            FOR EACH ROW
            BEGIN
                DECLARE bulan INT;
                DECLARE tahun_pembayaran VARCHAR(4);
                DECLARE id_debit BIGINT;
                DECLARE id_kredit BIGINT;
                DECLARE kode_debit_clean VARCHAR(50);
                DECLARE kode_kredit_clean VARCHAR(50);
                
                -- Reverse values ONLY IF it was NOT already soft-deleted
                IF OLD.deleted_at IS NULL THEN
                    SET bulan = MONTH(OLD.tanggal_pembayaran);
                    SET tahun_pembayaran = YEAR(OLD.tanggal_pembayaran);
                    SET kode_debit_clean = REPLACE(OLD.rekening_debit, ".", "");
                    SET kode_kredit_clean = REPLACE(OLD.rekening_kredit, ".", "");
                    SET id_debit = CAST(CONCAT(kode_debit_clean, tahun_pembayaran, OLD.business_id) AS UNSIGNED);
                    SET id_kredit = CAST(CONCAT(kode_kredit_clean, tahun_pembayaran, OLD.business_id) AS UNSIGNED);
                    
                    UPDATE balances SET
                        debit_01 = CASE WHEN bulan = 1 THEN debit_01 - OLD.total_harga ELSE debit_01 END,
                        debit_02 = CASE WHEN bulan = 2 THEN debit_02 - OLD.total_harga ELSE debit_02 END,
                        debit_03 = CASE WHEN bulan = 3 THEN debit_03 - OLD.total_harga ELSE debit_03 END,
                        debit_04 = CASE WHEN bulan = 4 THEN debit_04 - OLD.total_harga ELSE debit_04 END,
                        debit_05 = CASE WHEN bulan = 5 THEN debit_05 - OLD.total_harga ELSE debit_05 END,
                        debit_06 = CASE WHEN bulan = 6 THEN debit_06 - OLD.total_harga ELSE debit_06 END,
                        debit_07 = CASE WHEN bulan = 7 THEN debit_07 - OLD.total_harga ELSE debit_07 END,
                        debit_08 = CASE WHEN bulan = 8 THEN debit_08 - OLD.total_harga ELSE debit_08 END,
                        debit_09 = CASE WHEN bulan = 9 THEN debit_09 - OLD.total_harga ELSE debit_09 END,
                        debit_10 = CASE WHEN bulan = 10 THEN debit_10 - OLD.total_harga ELSE debit_10 END,
                        debit_11 = CASE WHEN bulan = 11 THEN debit_11 - OLD.total_harga ELSE debit_11 END,
                        debit_12 = CASE WHEN bulan = 12 THEN debit_12 - OLD.total_harga ELSE debit_12 END,
                        updated_at = NOW()
                    WHERE id = id_debit;
                    
                    UPDATE balances SET
                        kredit_01 = CASE WHEN bulan = 1 THEN kredit_01 - OLD.total_harga ELSE kredit_01 END,
                        kredit_02 = CASE WHEN bulan = 2 THEN kredit_02 - OLD.total_harga ELSE kredit_02 END,
                        kredit_03 = CASE WHEN bulan = 3 THEN kredit_03 - OLD.total_harga ELSE kredit_03 END,
                        kredit_04 = CASE WHEN bulan = 4 THEN kredit_04 - OLD.total_harga ELSE kredit_04 END,
                        kredit_05 = CASE WHEN bulan = 5 THEN kredit_05 - OLD.total_harga ELSE kredit_05 END,
                        kredit_06 = CASE WHEN bulan = 6 THEN kredit_06 - OLD.total_harga ELSE kredit_06 END,
                        kredit_07 = CASE WHEN bulan = 7 THEN kredit_07 - OLD.total_harga ELSE kredit_07 END,
                        kredit_08 = CASE WHEN bulan = 8 THEN kredit_08 - OLD.total_harga ELSE kredit_08 END,
                        kredit_09 = CASE WHEN bulan = 9 THEN kredit_09 - OLD.total_harga ELSE kredit_09 END,
                        kredit_10 = CASE WHEN bulan = 10 THEN kredit_10 - OLD.total_harga ELSE kredit_10 END,
                        kredit_11 = CASE WHEN bulan = 11 THEN kredit_11 - OLD.total_harga ELSE kredit_11 END,
                        kredit_12 = CASE WHEN bulan = 12 THEN kredit_12 - OLD.total_harga ELSE kredit_12 END,
                        updated_at = NOW()
                    WHERE id = id_kredit;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is tricky because rolling back should ideally restore the triggers to their previous state.
        // However, standard Laravel migrations usually just drop them or restore a simplified version.
        // Since we are adding SoftDeletes, rolling back this migration would likely be done in conjunction with 
        // rolling back the SoftDeletes migration itself.
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_update');
        DB::unprepared('DROP TRIGGER IF EXISTS after_payment_delete');
        
        // Optionally restore previous logic here if needed, but usually dropping is acceptable for a "fix" migration.
    }
};
