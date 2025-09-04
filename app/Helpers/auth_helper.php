<?php

if (!function_exists('role_level')) {
    function role_level(): int
    {
        return (int) (session('role') ?? -1); // -1 = não logado
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return role_level() === 2;
    }
}
if (!function_exists('is_gerente')) {
    function is_gerente(): bool
    {
        return role_level() === 1;
    }
}
if (!function_exists('is_vendedor')) {
    function is_vendedor(): bool
    {
        return role_level() === 0;
    }
}
if (!function_exists('has_min_role')) {
    function has_min_role(int $min): bool
    {
        return role_level() >= $min;
    }
}
