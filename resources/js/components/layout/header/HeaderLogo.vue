<template>
    <Link href="/" class="lg:hidden">
        <img
            class="dark:hidden w-[100px]"
            src="/images/logo/logo.png"
            alt="Logo"
        />
        <img
            class="hidden dark:block w-[100px]"
            src="/images/logo/logo-dark.png"
            alt="Logo"
        />
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
</script>
