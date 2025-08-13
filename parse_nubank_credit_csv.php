<?php

/**
 * Stub for parsing Nubank credit card CSV files.
 *
 * @param string $path Path to the CSV file.
 * @param int $accountId Related account ID.
 * @param string $tipo Transaction type.
 * @return array Parsed transactions (empty until implemented).
 */
function parse_nubank_credit_csv(string $path, int $accountId, string $tipo): array
{
    // TODO: Implement CSV parsing logic and call insert_transaction($accountId, ..., $tipo, null, $path).
    return [];
}

?>
