<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppSidebar from "../Components/AppSidebar.vue";

defineProps<{
    title?: string;
}>();

function logout() {
    Swal.fire({
        title: "Sign Out?",
        text: "Are you sure you want to end your current session?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3b82f6",
        cancelButtonColor: "#334155",
        confirmButtonText: "Yes, Logout",
        cancelButtonText: "Cancel",
        background: "#0f172a",
        color: "#f8fafc",
        customClass: {
            popup: "rounded-2xl border border-slate-800",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.post("/logout");
        }
    });
}
</script>

<template>
    <Head :title="title ?? 'RYUX Drive'" />

    <div
        class="h-screen overflow-hidden
               bg-slate-950 text-slate-100 font-sans"
    >
        <AppSidebar />

        <div
            class="ml-64 h-screen
                   overflow-y-auto overflow-x-hidden"
        >
            <!-- Header -->
            <header
                class="h-16
                       border-b border-slate-800/80
                       px-8
                       flex items-center justify-between
                       bg-slate-900/80
                       backdrop-blur-md
                       sticky top-0 z-40"
            >
                <div>
                    <h2
                        class="text-base font-semibold
                               text-slate-100"
                    >
                        {{ title ?? "Dashboard" }}
                    </h2>

                    <p
                        class="text-[11px]
                               text-slate-500 mt-0.5"
                    >
                        Google Drive overview
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="px-2.5 py-1 rounded-full
                               text-[10px] font-medium
                               bg-emerald-500/10
                               text-emerald-400
                               border border-emerald-500/20"
                    >
                        ● Connected
                    </span>

                    <button
                        @click="logout"
                        class="flex items-center
                               gap-1.5
                               px-3 py-1.5
                               rounded-xl
                               text-xs font-medium
                               text-slate-300
                               hover:text-rose-400
                               bg-slate-900
                               hover:bg-rose-500/10
                               border border-slate-800
                               hover:border-rose-500/20
                               transition-all"
                    >
                        Logout
                    </button>
                </div>
            </header>

            <!-- Dynamic Page -->
            <main
                class="p-6 max-w-7xl
                       mx-auto space-y-6"
            >
                <slot />
            </main>
        </div>
    </div>
</template>