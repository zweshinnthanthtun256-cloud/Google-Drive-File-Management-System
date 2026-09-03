<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import DriveLayout from "../../Layouts/DriveLayout.vue";

interface DriveFile {
    id: string;
    name: string;
    mimeType: string;
    size?: string;
    createdTime?: string;
    modifiedTime?: string;
    parents?: string[];
    canDownload?: boolean;
    canDelete?: boolean;
}

interface Folder {
    id: string;
    name: string;
}

const props = defineProps<{
    files: DriveFile[];

    folders: Folder[];

    filters: {
        from?: string;
        to?: string;
        search?: string;
        folder_id?: string;
        type?: string;
        page_token?: string;
        per_page?: number;
    };

    pagination?: {
        nextPageToken?: string | null;
        currentPageToken?: string | null;
    };
}>();

/*
|--------------------------------------------------------------------------
| Active Tab
|--------------------------------------------------------------------------
*/

const activeTab = ref<"files" | "upload">("files");

/*
|--------------------------------------------------------------------------
| Filter State
|--------------------------------------------------------------------------
*/

const showFilters = ref(false);

const from = ref(props.filters.from ?? "");

const to = ref(props.filters.to ?? "");

const search = ref(props.filters.search ?? "");

const folderId = ref(props.filters.folder_id ?? "");

const type = ref(props.filters.type ?? "");

const perPage = ref(props.filters.per_page ?? 10);

/*
|--------------------------------------------------------------------------
| Upload State
|--------------------------------------------------------------------------
*/

const selectedFile = ref<File | null>(null);

const uploading = ref(false);

const isDragging = ref(false);

const fileInputRef = ref<HTMLInputElement | null>(null);

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function goToFiles() {
    activeTab.value = "files";

    router.get(
        "/drive",
        {},
        {
            preserveState: false,
            replace: true,
        }
    );
}

function goToUpload() {
    activeTab.value = "upload";

    router.get(
        "/drive",
        {
            tab: "upload",
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

/*
|--------------------------------------------------------------------------
| Detect Upload Tab From URL
|--------------------------------------------------------------------------
*/

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get("tab") === "upload") {
        activeTab.value = "upload";
    } else {
        activeTab.value = "files";
    }
});

/*
|--------------------------------------------------------------------------
| Search / Filter
|--------------------------------------------------------------------------
*/

function searchFiles(token?: string) {
    activeTab.value = "files";

    router.get(
        "/drive",
        {
            from: from.value || undefined,
            to: to.value || undefined,
            search: search.value || undefined,
            folder_id: folderId.value || undefined,
            type: type.value || undefined,
            per_page: perPage.value,
            page_token: token || undefined,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
}

/*
|--------------------------------------------------------------------------
| Clear Filters
|--------------------------------------------------------------------------
*/

function clearFilters() {
    from.value = "";

    to.value = "";

    search.value = "";

    folderId.value = "";

    type.value = "";

    perPage.value = 10;

    router.get(
        "/drive",
        {},
        {
            preserveState: false,
            replace: true,
        }
    );
}

/*
|--------------------------------------------------------------------------
| Download
|--------------------------------------------------------------------------
*/

function downloadFile(file: DriveFile) {
    Swal.fire({
        title: "Downloading...",
        text: `Preparing "${file.name}"`,
        icon: "info",
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: "top-end",
        background: "#0f172a",
        color: "#f8fafc",
    });

    window.location.href = `/drive/${file.id}/download`;
}

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

function deleteFile(file: DriveFile) {
    Swal.fire({
        title: "Delete File?",
        text: `Permanently remove "${file.name}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#f43f5e",
        cancelButtonColor: "#334155",
        confirmButtonText: "Yes, Delete",
        cancelButtonText: "Cancel",
        background: "#0f172a",
        color: "#f8fafc",
        customClass: {
            popup: "rounded-2xl border border-slate-800",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/drive/${file.id}`, {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        title: "Deleted!",
                        text: "File has been removed.",
                        icon: "success",
                        background: "#0f172a",
                        color: "#f8fafc",
                        confirmButtonColor: "#3b82f6",
                    });
                },

                onError: () => {
                    Swal.fire({
                        title: "Delete Failed",
                        text: "Unable to delete the file.",
                        icon: "error",
                        background: "#0f172a",
                        color: "#f8fafc",
                    });
                },
            });
        }
    });
}

/*
|--------------------------------------------------------------------------
| Drag & Drop
|--------------------------------------------------------------------------
*/

function triggerFilePicker() {
    fileInputRef.value?.click();
}

function handleDragOver(e: DragEvent) {
    e.preventDefault();

    isDragging.value = true;
}

function handleDragLeave(e: DragEvent) {
    e.preventDefault();

    isDragging.value = false;
}

function handleDrop(e: DragEvent) {
    e.preventDefault();

    isDragging.value = false;

    if (
        e.dataTransfer?.files &&
        e.dataTransfer.files.length > 0
    ) {
        selectedFile.value = e.dataTransfer.files[0];
    }
}

function chooseFile(event: Event) {
    const target = event.target as HTMLInputElement;

    selectedFile.value = target.files?.[0] ?? null;
}

function removeSelectedFile() {
    selectedFile.value = null;

    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
}

/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
*/

function uploadFile() {
    if (!selectedFile.value) {
        Swal.fire({
            title: "No File Selected",
            text: "Please select a file first.",
            icon: "warning",
            background: "#0f172a",
            color: "#f8fafc",
            confirmButtonColor: "#3b82f6",
        });

        return;
    }

    const formData = new FormData();

    formData.append(
        "file",
        selectedFile.value
    );

    if (folderId.value) {
        formData.append(
            "folder_id",
            folderId.value
        );
    }

    uploading.value = true;

    Swal.fire({
        title: "Uploading...",
        text: "Transferring file to Google Drive",
        allowOutsideClick: false,
        background: "#0f172a",
        color: "#f8fafc",
        didOpen: () => {
            Swal.showLoading();
        },
    });

    router.post(
        "/drive/upload",
        formData,
        {
            forceFormData: true,

            onFinish: () => {
                uploading.value = false;
            },

            onSuccess: () => {
                removeSelectedFile();

                Swal.fire({
                    title: "Success!",
                    text: "File uploaded successfully.",
                    icon: "success",
                    background: "#0f172a",
                    color: "#f8fafc",
                    confirmButtonColor: "#3b82f6",
                }).then(() => {
                    activeTab.value = "files";

                    router.get(
                        "/drive",
                        {},
                        {
                            preserveState: false,
                            replace: true,
                        }
                    );
                });
            },

            onError: () => {
                Swal.fire({
                    title: "Upload Failed",
                    text: "Unable to upload the file.",
                    icon: "error",
                    background: "#0f172a",
                    color: "#f8fafc",
                    confirmButtonColor: "#3b82f6",
                });
            },
        }
    );
}

/*
|--------------------------------------------------------------------------
| File Size
|--------------------------------------------------------------------------
*/

function formatSize(
    size?: string | number
) {
    if (!size) {
        return "-";
    }

    const bytes = Number(size);

    if (!Number.isFinite(bytes)) {
        return "-";
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(
            bytes / 1024
        ).toFixed(1)} KB`;
    }

    if (
        bytes <
        1024 * 1024 * 1024
    ) {
        return `${(
            bytes /
            1024 /
            1024
        ).toFixed(1)} MB`;
    }

    return `${(
        bytes /
        1024 /
        1024 /
        1024
    ).toFixed(1)} GB`;
}

/*
|--------------------------------------------------------------------------
| Date
|--------------------------------------------------------------------------
*/

function formatDate(
    date?: string
) {
    if (!date) {
        return "-";
    }

    const parsedDate = new Date(date);

    if (
        Number.isNaN(
            parsedDate.getTime()
        )
    ) {
        return "-";
    }

    return parsedDate.toLocaleString(
        undefined,
        {
            dateStyle: "medium",
            timeStyle: "short",
        }
    );
}

/*
|--------------------------------------------------------------------------
| File Type Display
|--------------------------------------------------------------------------
*/

function getFileType(
    mimeType: string,
    fileName: string
) {
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

    if (
        mimeType.startsWith(
            "image/"
        )
    ) {
        return "image";
    }

    if (
        mimeType.startsWith(
            "text/"
        )
    ) {
        return "text";
    }

    return mimeType;
}
</script>

<template>
    <DriveLayout
        :title="
            activeTab === 'files'
                ? 'Files Explorer'
                : 'Upload Center'
        "
    >
        <!-- ===================================================== -->
        <!-- PAGE CONTENT -->
        <!-- ===================================================== -->

        <div class="space-y-4">

            <!-- ================================================= -->
            <!-- FILES EXPLORER -->
            <!-- ================================================= -->

            <template
                v-if="activeTab === 'files'"
            >

                <!-- ================================================= -->
                <!-- SEARCH + FILTER -->
                <!-- ================================================= -->

                <section class="space-y-3">

                    <!-- Search -->

                    <div
                        class="flex items-center gap-2"
                    >
                        <div
                            class="relative flex-1 min-w-0"
                        >
                            <span
                                class="absolute
                                       inset-y-0 left-0
                                       flex items-center
                                       pl-3.5
                                       pointer-events-none
                                       text-slate-500
                                       text-xs"
                            >
                                🔍
                            </span>

                            <input
                                v-model="search"
                                @keyup.enter="
                                    searchFiles()
                                "
                                type="text"
                                placeholder="Search files by name..."
                                class="w-full
                                       bg-slate-900/90
                                       border border-slate-800
                                       rounded-xl
                                       pl-9 pr-4 py-2.5
                                       text-xs
                                       text-slate-200
                                       placeholder-slate-500
                                       focus:outline-none
                                       focus:border-blue-500/50
                                       focus:ring-1
                                       focus:ring-blue-500/50
                                       transition-all
                                       shadow-inner"
                            />
                        </div>

                        <button
                            @click="searchFiles()"
                            class="px-4 py-2.5
                                   bg-blue-600
                                   hover:bg-blue-500
                                   text-white
                                   font-medium
                                   text-xs
                                   rounded-xl
                                   transition-all
                                   shadow-lg
                                   shadow-blue-600/20
                                   active:scale-95
                                   shrink-0"
                        >
                            Search
                        </button>
                    </div>

                    <!-- FILTER TRAY -->

                    <div
                        v-if="showFilters"
                        class="bg-slate-900/80
                               border border-slate-800/80
                               p-4
                               rounded-2xl
                               space-y-3
                               backdrop-blur-sm
                               shadow-xl"
                    >

                        <div
                            class="flex items-center
                                   justify-between
                                   border-b
                                   border-slate-800/60
                                   pb-2"
                        >
                            <span
                                class="text-[11px]
                                       font-semibold
                                       text-slate-400
                                       uppercase
                                       tracking-wider
                                       flex items-center
                                       gap-1.5"
                            >
                                <span>⚙️</span>

                                Advanced Search Parameters
                            </span>

                            <button
                                @click="clearFilters"
                                class="text-[11px]
                                       text-slate-400
                                       hover:text-rose-400
                                       transition-colors"
                            >
                                Reset all filters
                            </button>
                        </div>

                        <div
                            class="grid
                                   grid-cols-1
                                   sm:grid-cols-2
                                   lg:grid-cols-4
                                   gap-3
                                   text-xs"
                        >

                            <!-- Type -->

                            <div>
                                <label
                                    class="block
                                           text-slate-400
                                           text-[11px]
                                           font-medium
                                           mb-1"
                                >
                                    File Type
                                </label>

                                <select
                                    v-model="type"
                                    class="w-full
                                           bg-slate-950/80
                                           border border-slate-800
                                           rounded-xl
                                           px-3 py-2
                                           text-slate-200
                                           focus:outline-none
                                           focus:border-blue-500/60
                                           transition-all"
                                >
                                    <option value="">
                                        All Types
                                    </option>

                                    <option value="pdf">
                                        PDF
                                    </option>

                                    <option value="word">
                                        Word Documents
                                    </option>

                                    <option value="excel">
                                        Excel
                                    </option>

                                    <option value="powerpoint">
                                        PowerPoint
                                    </option>

                                    <option value="image">
                                        Images
                                    </option>

                                    <option value="text">
                                        Text
                                    </option>

                                    <option value="csv">
                                        CSV
                                    </option>

                                    <option value="zip">
                                        ZIP
                                    </option>
                                </select>
                            </div>

                            <!-- Folder -->

                            <div>
                                <label
                                    class="block
                                           text-slate-400
                                           text-[11px]
                                           font-medium
                                           mb-1"
                                >
                                    Folder
                                </label>

                                <select
                                    v-model="folderId"
                                    class="w-full
                                           bg-slate-950/80
                                           border border-slate-800
                                           rounded-xl
                                           px-3 py-2
                                           text-slate-200
                                           focus:outline-none
                                           focus:border-blue-500/60
                                           transition-all"
                                >
                                    <option value="">
                                        All Files
                                    </option>

                                    <option
                                        v-for="folder in folders"
                                        :key="folder.id"
                                        :value="folder.id"
                                    >
                                        {{ folder.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- From -->

                            <div>
                                <label
                                    class="block
                                           text-slate-400
                                           text-[11px]
                                           font-medium
                                           mb-1"
                                >
                                    From Date
                                </label>

                                <input
                                    v-model="from"
                                    type="date"
                                    class="w-full
                                           bg-slate-950/80
                                           border border-slate-800
                                           rounded-xl
                                           px-3 py-2
                                           text-slate-200
                                           focus:outline-none
                                           focus:border-blue-500/60
                                           transition-all"
                                />
                            </div>

                            <!-- To -->

                            <div>
                                <label
                                    class="block
                                           text-slate-400
                                           text-[11px]
                                           font-medium
                                           mb-1"
                                >
                                    To Date
                                </label>

                                <input
                                    v-model="to"
                                    type="date"
                                    class="w-full
                                           bg-slate-950/80
                                           border border-slate-800
                                           rounded-xl
                                           px-3 py-2
                                           text-slate-200
                                           focus:outline-none
                                           focus:border-blue-500/60
                                           transition-all"
                                />
                            </div>

                        </div>

                        <div
                            class="flex justify-end pt-1"
                        >
                            <button
                                @click="
                                    searchFiles()
                                "
                                class="px-4 py-1.5
                                       bg-slate-800
                                       hover:bg-slate-700
                                       border border-slate-700
                                       text-slate-200
                                       font-medium
                                       rounded-xl
                                       text-xs
                                       transition-all
                                       shadow-sm
                                       active:scale-95"
                            >
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </section>

                <!-- ================================================= -->
                <!-- FILE TABLE -->
                <!-- ================================================= -->

                <section
                    class="bg-slate-900/40
                           border border-slate-800/80
                           rounded-2xl
                           overflow-hidden
                           backdrop-blur-sm
                           shadow-sm"
                >

                    <div
                        class="overflow-x-auto"
                    >
                        <table
                            class="w-full
                                   text-left
                                   border-collapse
                                   min-w-[800px]"
                        >

                            <!-- Table Header -->

                            <thead>
                                <tr
                                    class="bg-slate-900/90
                                           border-b
                                           border-slate-800/80
                                           text-[11px]
                                           font-semibold
                                           text-slate-400
                                           uppercase
                                           tracking-wider"
                                >
                                    <th
                                        class="px-5 py-3"
                                    >
                                        Name
                                    </th>

                                    <th
                                        class="px-5 py-3"
                                    >
                                        Type
                                    </th>

                                    <th
                                        class="px-5 py-3"
                                    >
                                        Size
                                    </th>

                                    <th
                                        class="px-5 py-3"
                                    >
                                        Modified
                                    </th>

                                    <th
                                        class="px-5 py-3
                                               text-right"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <!-- Table Body -->

                            <tbody
                                class="divide-y
                                       divide-slate-800/50
                                       text-xs"
                            >

                                <!-- Files -->

                                <tr
                                    v-for="file in files"
                                    :key="file.id"
                                    class="hover:bg-slate-800/30
                                           transition-colors
                                           group"
                                >

                                    <!-- Name -->

                                    <td
                                        class="px-5 py-3.5
                                               font-medium
                                               text-slate-200
                                               max-w-xs"
                                    >
                                        <div
                                            class="truncate
                                                   max-w-[280px]"
                                            :title="file.name"
                                        >
                                            {{ file.name }}
                                        </div>
                                    </td>

                                    <!-- Type -->

                                    <td
                                        class="px-5 py-3.5"
                                    >
                                        <span
                                            class="px-2 py-0.5
                                                   rounded
                                                   bg-slate-800/80
                                                   text-slate-400
                                                   font-mono
                                                   text-[10px]
                                                   border
                                                   border-slate-700/50
                                                   uppercase"
                                        >
                                            {{
                                                getFileType(
                                                    file.mimeType,
                                                    file.name
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <!-- Size -->

                                    <td
                                        class="px-5 py-3.5
                                               font-mono
                                               text-slate-400"
                                    >
                                        {{
                                            formatSize(
                                                file.size
                                            )
                                        }}
                                    </td>

                                    <!-- Modified -->

                                    <td
                                        class="px-5 py-3.5
                                               text-slate-400"
                                    >
                                        {{
                                            formatDate(
                                                file.modifiedTime
                                            )
                                        }}
                                    </td>

                                    <!-- Actions -->

                                    <td
                                        class="px-5 py-3.5
                                               text-right"
                                    >
                                        <div
                                            class="flex
                                                   justify-end
                                                   gap-2"
                                        >

                                            <!-- Download -->

                                            <button
                                                v-if="
                                                    file.canDownload !==
                                                    false
                                                "
                                                @click="
                                                    downloadFile(
                                                        file
                                                    )
                                                "
                                                class="px-2.5 py-1
                                                       text-[11px]
                                                       font-medium
                                                       text-blue-400
                                                       bg-blue-500/10
                                                       hover:bg-blue-500/20
                                                       border
                                                       border-blue-500/20
                                                       rounded-lg
                                                       transition-all"
                                            >
                                                Download
                                            </button>

                                            <!-- Delete -->

                                            <button
                                                v-if="
                                                    file.canDelete !==
                                                    false
                                                "
                                                @click="
                                                    deleteFile(
                                                        file
                                                    )
                                                "
                                                class="px-2.5 py-1
                                                       text-[11px]
                                                       font-medium
                                                       text-rose-400
                                                       bg-rose-500/10
                                                       hover:bg-rose-500/20
                                                       border
                                                       border-rose-500/20
                                                       rounded-lg
                                                       transition-all"
                                            >
                                                Delete
                                            </button>

                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty -->

                                <tr
                                    v-if="
                                        files.length === 0
                                    "
                                >
                                    <td
                                        colspan="5"
                                        class="text-center
                                               py-16
                                               text-slate-500"
                                    >
                                        <div
                                            class="flex
                                                   flex-col
                                                   items-center
                                                   gap-2"
                                        >
                                            <span
                                                class="text-3xl
                                                       opacity-50"
                                            >
                                                📂
                                            </span>

                                            <span>
                                                No assets found
                                                matching your query.
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <!-- ================================================= -->
                    <!-- PAGINATION -->
                    <!-- ================================================= -->

                    <div
                        class="px-5 py-3.5
                               border-t
                               border-slate-800/80
                               flex items-center
                               justify-between
                               gap-4
                               bg-slate-900/60
                               text-xs"
                    >

                        <span
                            class="text-slate-400"
                        >
                            Showing maximum

                            <strong
                                class="text-slate-200"
                            >
                                {{ perPage }}
                            </strong>

                            items per page
                        </span>

                        <div
                            class="flex items-center gap-2"
                        >
                            <button
                                v-if="
                                    pagination?.nextPageToken
                                "
                                @click="
                                    searchFiles(
                                        pagination.nextPageToken
                                    )
                                "
                                class="px-3.5 py-1.5
                                       bg-blue-600
                                       hover:bg-blue-500
                                       text-white
                                       rounded-lg
                                       font-medium
                                       transition-all
                                       shadow-md
                                       shadow-blue-600/20
                                       active:scale-95"
                            >
                                Next Page →
                            </button>
                        </div>
                    </div>

                </section>

            </template>

            <!-- ================================================= -->
            <!-- UPLOAD CENTER -->
            <!-- ================================================= -->

            <template
                v-else-if="
                    activeTab === 'upload'
                "
            >

                <section
                    class="bg-slate-900/40
                           border border-slate-800/80
                           p-6
                           rounded-2xl
                           max-w-2xl
                           w-full
                           mx-auto
                           space-y-5
                           backdrop-blur-sm
                           shadow-sm"
                >

                    <!-- Heading -->

                    <div>
                        <div
                            class="flex items-center
                                   gap-3 mb-1"
                        >
                            <div
                                class="w-9 h-9
                                       rounded-xl
                                       bg-blue-500/10
                                       border
                                       border-blue-500/20
                                       flex items-center
                                       justify-center"
                            >
                                🚀
                            </div>

                            <h3
                                class="font-bold
                                       text-slate-100
                                       text-base"
                            >
                                Upload Asset
                            </h3>
                        </div>

                        <p
                            class="text-xs
                                   text-slate-400
                                   mt-2"
                        >
                            Select a folder and upload
                            your files directly to
                            Google Drive.
                        </p>
                    </div>

                    <!-- Folder -->

                    <div>
                        <label
                            class="block
                                   text-xs
                                   font-medium
                                   text-slate-300
                                   mb-1.5"
                        >
                            Destination Folder
                        </label>

                        <select
                            v-model="folderId"
                            class="w-full
                                   bg-slate-950
                                   border border-slate-800
                                   rounded-xl
                                   px-3 py-2.5
                                   text-xs
                                   text-slate-200
                                   focus:outline-none
                                   focus:border-blue-500
                                   transition-all"
                        >
                            <option value="">
                                Root Directory
                            </option>

                            <option
                                v-for="folder in folders"
                                :key="folder.id"
                                :value="folder.id"
                            >
                                📁 {{ folder.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Drag Zone -->

                    <div
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                        @click="triggerFilePicker"
                        :class="[
                            'border-2 border-dashed',
                            'rounded-2xl',
                            'p-8',
                            'text-center',
                            'transition-all',
                            'cursor-pointer',
                            'flex flex-col',
                            'items-center',
                            'justify-center',
                            'min-h-[210px]',

                            isDragging
                                ? 'border-blue-500 bg-blue-500/10'
                                : selectedFile
                                ? 'border-emerald-500/50 bg-emerald-500/5'
                                : 'border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-950',
                        ]"
                    >

                        <input
                            ref="fileInputRef"
                            type="file"
                            @change="chooseFile"
                            class="hidden"
                        />

                        <!-- Empty -->

                        <div
                            v-if="!selectedFile"
                            class="space-y-3"
                        >
                            <div
                                class="w-14 h-14
                                       rounded-2xl
                                       bg-slate-800
                                       text-blue-400
                                       flex items-center
                                       justify-center
                                       text-2xl
                                       mx-auto
                                       border
                                       border-slate-700"
                            >
                                ☁️
                            </div>

                            <div>
                                <p
                                    class="text-xs
                                           text-slate-300"
                                >
                                    <span
                                        class="text-blue-400
                                               underline
                                               font-medium"
                                    >
                                        Click to upload
                                    </span>

                                    or drag files here
                                </p>

                                <p
                                    class="text-[10px]
                                           text-slate-600
                                           mt-1"
                                >
                                    Files will be uploaded
                                    directly to Google Drive
                                </p>
                            </div>
                        </div>

                        <!-- Selected -->

                        <div
                            v-else
                            class="flex items-center
                                   justify-between
                                   gap-4
                                   w-full
                                   bg-slate-900
                                   p-3
                                   rounded-xl
                                   border
                                   border-slate-800"
                        >

                            <div
                                class="text-left
                                       truncate
                                       min-w-0"
                            >
                                <p
                                    class="text-xs
                                           font-medium
                                           text-slate-200
                                           truncate"
                                >
                                    {{ selectedFile.name }}
                                </p>

                                <p
                                    class="text-[10px]
                                           text-slate-500
                                           mt-1"
                                >
                                    {{
                                        formatSize(
                                            selectedFile.size
                                        )
                                    }}
                                </p>
                            </div>

                            <button
                                @click.stop="
                                    removeSelectedFile
                                "
                                class="text-xs
                                       text-rose-400
                                       hover:underline
                                       shrink-0"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Upload Button -->

                    <div
                        class="flex justify-end
                               gap-3 pt-2"
                    >
                        <button
                            @click="uploadFile"
                            :disabled="
                                uploading ||
                                !selectedFile
                            "
                            class="px-5 py-2.5
                                   bg-blue-600
                                   hover:bg-blue-500
                                   text-white
                                   font-medium
                                   text-xs
                                   rounded-xl
                                   transition-all
                                   disabled:opacity-50
                                   disabled:cursor-not-allowed
                                   shadow-lg
                                   shadow-blue-600/20"
                        >
                            {{
                                uploading
                                    ? "Uploading..."
                                    : "Start Upload"
                            }}
                        </button>
                    </div>

                </section>

            </template>

        </div>
    </DriveLayout>
</template>