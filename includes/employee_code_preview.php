<?php

function employee_code_previews(PDO $pdo, array $roles): array
{
    $prefixes = ['0' => 'QL', '1' => 'NV', '2' => 'AD'];
    $codes = [];
    $lastCode = $pdo->prepare(
        'SELECT maNV
         FROM NHANVIEN
         WHERE maNV LIKE :prefix
         ORDER BY CAST(SUBSTRING(maNV, 3) AS UNSIGNED) DESC
         LIMIT 1'
    );

    foreach ($roles as $role) {
        $prefix = $prefixes[$role];
        $lastCode->execute(['prefix' => $prefix . '%']);
        $lastCodeValue = (string) ($lastCode->fetchColumn() ?: '');
        $lastNumber = preg_match('/^' . $prefix . '([0-9]{3})$/', $lastCodeValue, $matches)
            ? (int) $matches[1]
            : 0;
        $codes[$role] = $lastNumber >= 999
            ? 'Đã hết mã cho vai trò này'
            : $prefix . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }

    return $codes;
}
