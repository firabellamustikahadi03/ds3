<?php
// Guarded during DASS-21 migration: the old Gejala::InsertGejala()/EditGejala()/HapusGejala() signatures
// no longer match the new ds_symptoms schema, and this processor had no auth check of its own — disabled
// entirely rather than left reachable. Re-enable once Phase 2 rebuilds Admin gejala CRUD for the new schema.
header('Location: ../admin/symptoms.php');
exit;
