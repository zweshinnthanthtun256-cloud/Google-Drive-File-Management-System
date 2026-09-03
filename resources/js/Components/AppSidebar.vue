<script setup lang="ts">
import { router } from "@inertiajs/vue3";

function goToDashboard() {
    router.get("/dashboard");
}

function goToFiles() {
    router.get("/drive");
}

function goToUpload() {
    router.get("/drive", {
        tab: "upload",
    });
}
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 w-64
               bg-slate-900/95 backdrop-blur-xl
               border-r border-slate-800/80
               flex flex-col"
    >
        <!-- Logo -->
        <div
            class="p-5 flex items-center gap-3
                   border-b border-slate-800/60
                   shrink-0"
        >
            <div
                class="h-9 w-9 rounded-xl
                       bg-gradient-to-tr from-blue-600 to-indigo-500
                       flex items-center justify-center
                       text-white font-black text-lg"
            >
                R
            </div>

            <div>
                <h1 class="font-bold text-slate-100 text-sm">
                    RYUX Drive
                </h1>

                <span
                    class="text-[10px] text-blue-400
                           font-medium tracking-wider uppercase"
                >
                    Google Drive Management
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="p-3 space-y-1">
            <div
                class="px-3 py-2 text-[11px]
                       font-semibold text-slate-500
                       uppercase tracking-wider"
            >
                Menu
            </div>

            <button
                @click="goToDashboard"
                class="w-full text-left px-3 py-2.5
                       rounded-xl text-xs font-medium
                       flex items-center gap-2.5
                       text-slate-400
                       hover:bg-slate-800/50
                       hover:text-slate-200
                       transition-all"
            >
                <span class="text-base">📊</span>
                <span>Dashboard</span>
            </button>

            <button
                @click="goToFiles"
                class="w-full text-left px-3 py-2.5
                       rounded-xl text-xs font-medium
                       flex items-center gap-2.5
                       text-slate-400
                       hover:bg-slate-800/50
                       hover:text-slate-200
                       transition-all"
            >
                <span class="text-base">📂</span>
                <span>Files Explorer</span>
            </button>

            <button
                @click="goToUpload"
                class="w-full text-left px-3 py-2.5
                       rounded-xl text-xs font-medium
                       flex items-center gap-2.5
                       text-slate-400
                       hover:bg-slate-800/50
                       hover:text-slate-200
                       transition-all"
            >
                <span class="text-base">🚀</span>
                <span>Upload Storage</span>
            </button>
        </nav>
    </aside>
</template>