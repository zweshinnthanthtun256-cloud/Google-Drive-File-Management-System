<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import DriveLayout from "../Layouts/DriveLayout.vue";

interface DashboardFile {
    id: string;
    name: string;
    mimeType: string;
    size?: string | null;
    createdTime?: string | null;
    modifiedTime?: string | null;
    canDownload?: boolean;
    canDelete?: boolean;
}

interface FileTypeStat {
    type: string;
    count: number;
}

interface Storage {
    used: number;
    limit: number | null;
    usedFormatted: string;
    limitFormatted: string;
    percentage: number;
}

interface DashboardStats {
    totalFiles: number;
    totalFolders: number;
    recentUploads: number;
    storage: Storage;
    fileTypes: FileTypeStat[];
}

defineProps<{
    stats: DashboardStats;
    recentFiles: DashboardFile[];
}>();

function goToFiles() {
    router.get("/drive");
}

function goToUpload() {
    router.get("/drive", {
        tab: "upload",
    });
}

function formatSize(size?: string | number | null) {
    if (!size) {
        return "-";
    }

    const bytes = Number(size);

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    if (bytes < 1024 * 1024 * 1024) {
        return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    }

    return `${(bytes / 1024 / 1024 / 1024).toFixed(1)} GB`;
}

function formatDate(date?: string | null) {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function getFileType(mimeType: string, fileName: string) {
    const extension = fileName
        .split(".")
        .pop()
        ?.toLowerCase();

    if (extension) {
        return extension;
    }

    if (mimeType.includes("pdf")) {
        return "pdf";
    }

    if (
        mimeType.includes("word") ||
        mimeType.includes("document")
    ) {
        return "doc";
    }

    if (
        mimeType.includes("excel") ||
        mimeType.includes("spreadsheet")
    ) {
        return "xlsx";
    }

    if (
        mimeType.includes("powerpoint") ||
        mimeType.includes("presentation")
    ) {
        return "pptx";
    }

    if (mimeType.startsWith("image/")) {
        return "image";
    }

    return "file";
}

function getTypeClass(type: string) {
    const normalized = type.toLowerCase();

    if (normalized === "pdf") {
        return "text-rose-400 bg-rose-500/10 border-rose-500/20";
    }

    if (
        normalized === "xlsx" ||
        normalized === "xls" ||
        normalized === "csv"
    ) {
        return "text-emerald-400 bg-emerald-500/10 border-emerald-500/20";
    }

    if (
        normalized === "doc" ||
        normalized === "docx"
    ) {
        return "text-blue-400 bg-blue-500/10 border-blue-500/20";
    }

    if (
        normalized === "ppt" ||
        normalized === "pptx"
    ) {
        return "text-orange-400 bg-orange-500/10 border-orange-500/20";
    }

    if (
        normalized === "jpg" ||
        normalized === "jpeg" ||
        normalized === "png" ||
        normalized === "gif" ||
        normalized === "webp"
    ) {
        return "text-purple-400 bg-purple-500/10 border-purple-500/20";
    }

    return "text-slate-400 bg-slate-800 border-slate-700";
}

function downloadFile(file: DashboardFile) {
    window.location.href = `/drive/${file.id}/download`;
}
</script>

<template>
    <DriveLayout title="Dashboard">

        <!-- Welcome -->
        <section>
            <h1
                class="text-2xl font-bold text-slate-100"
            >
                Welcome to RYUX Drive
            </h1>

            <p
                class="text-sm text-slate-400 mt-1"
            >
                Manage and monitor your Google Drive
                files from one place.
            </p>
        </section>

        <!-- ================================================= -->
        <!-- STAT CARDS -->
        <!-- ================================================= -->

        <section
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4"
        >
            <!-- Total Files -->
            <div
                class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">
                            Total Files
                        </p>

                        <p
                            class="text-2xl font-bold
                                   text-slate-100 mt-2"
                        >
                            {{ stats.totalFiles.toLocaleString() }}
                        </p>
                    </div>

                    <div
                        class="w-11 h-11 rounded-xl
                               bg-blue-500/10
                               border border-blue-500/20
                               flex items-center justify-center
                               text-xl"
                    >
                        📄
                    </div>
                </div>
            </div>

            <!-- Total Folders -->
            <div
                class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">
                            Total Folders
                        </p>

                        <p
                            class="text-2xl font-bold
                                   text-slate-100 mt-2"
                        >
                            {{ stats.totalFolders.toLocaleString() }}
                        </p>
                    </div>

                    <div
                        class="w-11 h-11 rounded-xl
                               bg-amber-500/10
                               border border-amber-500/20
                               flex items-center justify-center
                               text-xl"
                    >
                        📁
                    </div>
                </div>
            </div>

            <!-- Recent -->
            <div
                class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">
                            Recent Files
                        </p>

                        <p
                            class="text-2xl font-bold
                                   text-slate-100 mt-2"
                        >
                            {{ stats.recentUploads.toLocaleString() }}
                        </p>

                        <p
                            class="text-[10px]
                                   text-slate-500 mt-1"
                        >
                            Last 7 days
                        </p>
                    </div>

                    <div
                        class="w-11 h-11 rounded-xl
                               bg-emerald-500/10
                               border border-emerald-500/20
                               flex items-center justify-center
                               text-xl"
                    >
                        🕐
                    </div>
                </div>
            </div>

            <!-- Storage -->
            <div
                class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">
                            Storage Used
                        </p>

                        <p
                            class="text-2xl font-bold
                                   text-slate-100 mt-2"
                        >
                            {{ stats.storage.usedFormatted }}
                        </p>
                    </div>

                    <div
                        class="w-11 h-11 rounded-xl
                               bg-purple-500/10
                               border border-purple-500/20
                               flex items-center justify-center
                               text-xl"
                    >
                        ☁️
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================= -->
        <!-- STORAGE -->
        <!-- ================================================= -->

        <section
            class="bg-slate-900/50
                   border border-slate-800
                   rounded-2xl p-5"
        >
            <div
                class="flex items-center justify-between mb-3"
            >
                <div>
                    <h3
                        class="text-sm font-semibold
                               text-slate-200"
                    >
                        Google Drive Storage
                    </h3>

                    <p
                        class="text-[11px]
                               text-slate-500 mt-1"
                    >
                        {{ stats.storage.usedFormatted }}
                        used

                        <template v-if="stats.storage.limit">
                            of
                            {{ stats.storage.limitFormatted }}
                        </template>
                    </p>
                </div>

                <span
                    class="text-sm font-semibold
                           text-blue-400"
                >
                    {{ stats.storage.percentage }}%
                </span>
            </div>

            <div
                class="h-2 bg-slate-800
                       rounded-full overflow-hidden"
            >
                <div
                    class="h-full
                           bg-gradient-to-r
                           from-blue-600 to-indigo-500
                           rounded-full transition-all"
                    :style="{
                        width:
                            Math.min(
                                stats.storage.percentage,
                                100
                            ) + '%',
                    }"
                ></div>
            </div>
        </section>

        <!-- ================================================= -->
        <!-- TWO COLUMNS -->
        <!-- ================================================= -->

        <section
            class="grid grid-cols-1 lg:grid-cols-3 gap-5"
        >
            <!-- Recent Files -->

            <div
                class="lg:col-span-2
                       bg-slate-900/50
                       border border-slate-800
                       rounded-2xl overflow-hidden"
            >
                <div
                    class="px-5 py-4
                           border-b border-slate-800
                           flex items-center justify-between"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold
                                   text-slate-200"
                        >
                            Recent Files
                        </h3>

                        <p
                            class="text-[10px]
                                   text-slate-500 mt-1"
                        >
                            Recently modified files
                        </p>
                    </div>

                    <button
                        @click="goToFiles"
                        class="text-[11px]
                               text-blue-400
                               hover:text-blue-300"
                    >
                        View All →
                    </button>
                </div>

                <div v-if="recentFiles.length > 0">
                    <div
                        v-for="file in recentFiles"
                        :key="file.id"
                        class="px-5 py-3.5
                               border-b border-slate-800/60
                               last:border-b-0
                               flex items-center gap-3
                               hover:bg-slate-800/30
                               transition-colors"
                    >
                        <div
                            class="w-9 h-9 rounded-lg
                                   bg-slate-800
                                   flex items-center
                                   justify-center
                                   shrink-0"
                        >
                            📄
                        </div>

                        <div class="flex-1 min-w-0">
                            <p
                                class="text-xs font-medium
                                       text-slate-200 truncate"
                            >
                                {{ file.name }}
                            </p>

                            <div
                                class="flex items-center
                                       gap-2 mt-1"
                            >
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded border text-[9px] uppercase',
                                        getTypeClass(
                                            getFileType(
                                                file.mimeType,
                                                file.name
                                            )
                                        ),
                                    ]"
                                >
                                    {{
                                        getFileType(
                                            file.mimeType,
                                            file.name
                                        )
                                    }}
                                </span>

                                <span
                                    class="text-[10px]
                                           text-slate-500"
                                >
                                    {{ formatSize(file.size) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="hidden sm:block
                                   text-right shrink-0"
                        >
                            <p
                                class="text-[10px]
                                       text-slate-500"
                            >
                                {{ formatDate(file.modifiedTime) }}
                            </p>
                        </div>

                        <button
                            v-if="file.canDownload !== false"
                            @click="downloadFile(file)"
                            class="px-2.5 py-1
                                   text-[10px]
                                   text-blue-400
                                   bg-blue-500/10
                                   border border-blue-500/20
                                   rounded-lg
                                   hover:bg-blue-500/20"
                        >
                            Download
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="py-12 text-center
                           text-xs text-slate-500"
                >
                    No files found.
                </div>
            </div>

            <!-- File Types -->

            <div
                class="bg-slate-900/50
                       border border-slate-800
                       rounded-2xl overflow-hidden"
            >
                <div
                    class="px-5 py-4
                           border-b border-slate-800"
                >
                    <h3
                        class="text-sm font-semibold
                               text-slate-200"
                    >
                        File Types
                    </h3>

                    <p
                        class="text-[10px]
                               text-slate-500 mt-1"
                    >
                        Files by category
                    </p>
                </div>

                <div class="p-5 space-y-4">
                    <div
                        v-if="stats.fileTypes.length === 0"
                        class="text-center
                               text-xs text-slate-500 py-6"
                    >
                        No file statistics available.
                    </div>

                    <div
                        v-for="item in stats.fileTypes"
                        :key="item.type"
                    >
                        <div
                            class="flex items-center
                                   justify-between mb-1.5"
                        >
                            <span
                                class="text-xs
                                       text-slate-400"
                            >
                                {{ item.type }}
                            </span>

                            <span
                                class="text-xs font-semibold
                                       text-slate-200"
                            >
                                {{ item.count.toLocaleString() }}
                            </span>
                        </div>

                        <div
                            class="h-1.5 bg-slate-800
                                   rounded-full overflow-hidden"
                        >
                            <div
                                class="h-full bg-blue-500
                                       rounded-full"
                                :style="{
                                    width:
                                        Math.max(
                                            5,
                                            (
                                                item.count /
                                                Math.max(
                                                    stats.totalFiles,
                                                    1
                                                )
                                            ) * 100
                                        ) + '%',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================= -->
        <!-- QUICK ACTIONS -->
        <!-- ================================================= -->

        <section>
            <h3
                class="text-sm font-semibold
                       text-slate-200 mb-3"
            >
                Quick Actions
            </h3>

            <div
                class="grid grid-cols-1
                       sm:grid-cols-3 gap-4"
            >
                <button
                    @click="goToFiles"
                    class="text-left p-5
                           bg-slate-900/50
                           border border-slate-800
                           hover:border-blue-500/30
                           hover:bg-blue-500/5
                           rounded-2xl transition-all"
                >
                    <div class="text-xl mb-3">📂</div>

                    <h4
                        class="text-xs font-semibold
                               text-slate-200"
                    >
                        Browse Files
                    </h4>

                    <p
                        class="text-[10px]
                               text-slate-500 mt-1"
                    >
                        Search, filter and manage your files.
                    </p>
                </button>

                <button
                    @click="goToUpload"
                    class="text-left p-5
                           bg-slate-900/50
                           border border-slate-800
                           hover:border-blue-500/30
                           hover:bg-blue-500/5
                           rounded-2xl transition-all"
                >
                    <div class="text-xl mb-3">🚀</div>

                    <h4
                        class="text-xs font-semibold
                               text-slate-200"
                    >
                        Upload File
                    </h4>

                    <p
                        class="text-[10px]
                               text-slate-500 mt-1"
                    >
                        Upload a new file to Google Drive.
                    </p>
                </button>

                <button
                    @click="goToFiles"
                    class="text-left p-5
                           bg-slate-900/50
                           border border-slate-800
                           hover:border-blue-500/30
                           hover:bg-blue-500/5
                           rounded-2xl transition-all"
                >
                    <div class="text-xl mb-3">🔍</div>

                    <h4
                        class="text-xs font-semibold
                               text-slate-200"
                    >
                        Search Files
                    </h4>

                    <p
                        class="text-[10px]
                               text-slate-500 mt-1"
                    >
                        Find files by name, type, date or folder.
                    </p>
                </button>
            </div>
        </section>

    </DriveLayout>
</template>