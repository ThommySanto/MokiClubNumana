-- ============================================================================
-- MIGRAZIONE: Aggiunta modalità tariffe agli eventi
-- Data: 2026-08-27
-- Descrizione: Aggiunge la possibilità di scegliere tra tariffazione
--   dettagliata (tesserato/non tesserato/tavola propria) e tariffazione
--   semplice (quota unica, senza tavola propria).
-- ============================================================================

-- 1. Aggiungi colonna modalita_tariffe alla tabella eventi
ALTER TABLE `eventi`
ADD COLUMN `modalita_tariffe` VARCHAR(50) NOT NULL DEFAULT 'dettagliata'
COMMENT 'dettagliata: 3 tariffe (tesserato, non_tesserato, tavola_propria) | semplice: quota unica'
AFTER `giorni_limite_modifica`;

-- 2. Verifica la colonna è stata aggiunta correttamente
SELECT id, titolo, modalita_tariffe FROM `eventi` LIMIT 1;

-- ============================================================================
-- NOTA IMPORTANTE
-- ============================================================================
-- Dopo aver eseguito questi comandi:
-- - Gli eventi ESISTENTI avranno automaticamente modalita_tariffe = 'dettagliata'
--   (il comportamento attuale)
-- - I NUOVI eventi creati dall'admin form avranno il default 'dettagliata'
-- - Quando modifichi un evento, potrai cambiare la modalità
-- ============================================================================
