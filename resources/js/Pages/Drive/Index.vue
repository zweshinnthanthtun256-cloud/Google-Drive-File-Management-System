<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import Swal from "sweetalert2";

interface DriveFile {
  id: string;
  name: string;
  mimeType: string;
  size?: string;
  createdTime?: string;
  modifiedTime?: string;
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
    page_token?: string;
    per_page?: number;
  };
  pagination?: {
    nextPageToken?: string | null;
    currentPageToken?: string | null;
  };
}>();

// Navigation state
const activeTab = ref<"dashboard" | "upload">("dashboard");

// Filter states
const showFilters = ref(false);
const from = ref(props.filters.from ?? "");
const to = ref(props.filters.to ?? "");
const search = ref(props.filters.search ?? "");
const folderId = ref(props.filters.folder_id ?? "");
const perPage = ref(props.filters.per_page ?? 10);

// Upload states
const selectedFile = ref<File | null>(null);
const uploading = ref(false);
const isDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

function logout() {
  Swal.fire({
    title: "Sign Out?",
    text: "Are you sure you want to end your current session?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3b82f6",
    cancelButtonColor: "#334155",
    confirmButtonText: "Yes, Logout",
    background: "#0f172a",
    color: "#f8fafc",
    customClass: { popup: "rounded-2xl border border-slate-800" },
  }).then((result) => {
    if (result.isConfirmed) {
      router.post("/logout");
    }
  });
}

function searchFiles(token?: string) {
  router.get(
    "/drive",
    {
      from: from.value || undefined,
      to: to.value || undefined,
      search: search.value || undefined,
      folder_id: folderId.value || undefined,
      per_page: perPage.value,
      page_token: token,
    },
    {
      preserveState: true,
      replace: true,
    }
  );
}

function clearFilters() {
  from.value = "";
  to.value = "";
  search.value = "";
  folderId.value = "";
  perPage.value = 10;
  router.get("/drive");
}

/* SweetAlert Action Handlers */
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

function deleteFile(file: DriveFile) {
  Swal.fire({
    title: "Delete File?",
    text: `Permanently remove "${file.name}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#f43f5e",
    cancelButtonColor: "#334155",
    confirmButtonText: "Yes, Delete",
    background: "#0f172a",
    color: "#f8fafc",
    customClass: { popup: "rounded-2xl border border-slate-800" },
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
          });
        },
      });
    }
  });
}

/* Drag and Drop Handlers */
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
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
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

function uploadFile() {
  if (!selectedFile.value) return;

  const formData = new FormData();
  formData.append("file", selectedFile.value);
  if (folderId.value) {
    formData.append("folder_id", folderId.value);
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

  router.post("/drive/upload", formData, {
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
      }).then(() => {
        activeTab.value = "dashboard";
      });
    },
  });
}

function formatSize(size?: string | number) {
  if (!size) return "-";
  const bytes = Number(size);
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
  return `${(bytes / 1024 / 1024 / 1024).toFixed(1)} GB`;
}

function formatDate(date?: string) {
  if (!date) return "-";
  return new Date(date).toLocaleString(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  });
}
</script>

<template>
  <Head title="Drive Studio" />

  <div class="min-h-screen flex bg-slate-950 text-slate-100 font-sans selection:bg-blue-500 selection:text-white">
    <!-- Premium Dark Sidebar -->
    <aside class="w-64 bg-slate-900/60 backdrop-blur-xl border-r border-slate-800/80 flex flex-col justify-between shrink-0">
      <div>
        <!-- Logo Header -->
        <div class="p-5 flex items-center gap-3 border-b border-slate-800/60">
          <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-blue-500/20">
            R
          </div>
          <div>
            <h1 class="font-bold text-slate-100 tracking-wide text-sm leading-tight">
              RYUX Drive
            </h1>
            <span class="text-[10px] text-blue-400 font-medium tracking-wider uppercase">Cloud Management</span>
          </div>
        </div>

        <!-- Navigation -->
        <div class="p-3 space-y-1">
          <div class="px-3 py-2 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
            Menu
          </div>

          <button
            @click="activeTab = 'dashboard'"
            :class="[
              'w-full text-left px-3 py-2.5 rounded-xl text-xs font-medium transition-all flex items-center justify-between group',
              activeTab === 'dashboard'
                ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20 shadow-sm'
                : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200',
            ]"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-base">📂</span>
              <span>Files Explorer</span>
            </div>
            <span v-if="files.length" class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 group-hover:bg-slate-700">
              {{ files.length }}
            </span>
          </button>

          <button
            @click="activeTab = 'upload'"
            :class="[
              'w-full text-left px-3 py-2.5 rounded-xl text-xs font-medium transition-all flex items-center gap-2.5',
              activeTab === 'upload'
                ? 'bg-blue-600/10 text-blue-400 border border-blue-500/20 shadow-sm'
                : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200',
            ]"
          >
            <span class="text-base">🚀</span>
            <span>Upload Storage</span>
          </button>
        </div>
      </div>

      <!-- User Action / Logout -->
      <div class="p-3 border-t border-slate-800/60">
        <button
          @click="logout"
          class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all"
        >
          <span>Logout Session</span>
        </button>
      </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-950">
      <!-- Navbar Header -->
      <header class="h-16 border-b border-slate-800/80 px-8 flex items-center justify-between bg-slate-900/30 backdrop-blur-md sticky top-0 z-10">
        <div class="flex items-center gap-3">
          <h2 class="text-base font-semibold text-slate-100 tracking-tight">
            {{ activeTab === 'dashboard' ? 'Files Dashboard' : 'Upload Center' }}
          </h2>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            Connected
          </span>
        </div>

        <!-- Dynamic Page Size & Quick Search Trigger -->
        <div v-if="activeTab === 'dashboard'" class="flex items-center gap-3">
          <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-2 py-1">
            <span class="text-xs text-slate-400 pl-1">Per Page:</span>
            <select
              v-model="perPage"
              @change="searchFiles()"
              class="bg-transparent text-xs font-semibold text-blue-400 focus:outline-none cursor-pointer pr-1"
            >
              <option :value="10" class="bg-slate-900 text-slate-200">10</option>
              <option :value="25" class="bg-slate-900 text-slate-200">25</option>
              <option :value="50" class="bg-slate-900 text-slate-200">50</option>
              <option :value="100" class="bg-slate-900 text-slate-200">100</option>
            </select>
          </div>

          <button
            @click="showFilters = !showFilters"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-medium transition-all flex items-center gap-1.5 border',
              showFilters || from || to || folderId
                ? 'bg-blue-600/10 border-blue-500/30 text-blue-400'
                : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200',
            ]"
          >
            <span>⚡ Filters</span>
            <span v-if="from || to || folderId" class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
          </button>
        </div>
      </header>

      <!-- Main Body Container -->
      <main class="p-6 max-w-7xl w-full mx-auto space-y-4 overflow-y-auto">
        <!-- ================= PANEL 1: DASHBOARD ================= -->
        <template v-if="activeTab === 'dashboard'">
          <!-- Inline Quick Search & Collapsible Compact Filters -->
          <section class="space-y-3">
            <div class="flex items-center gap-3">
              <div class="relative flex-1">
                <input
                  v-model="search"
                  @keyup.enter="searchFiles()"
                  type="text"
                  placeholder="Search files by name..."
                  class="w-full bg-slate-900/90 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/50 transition-all shadow-inner"
                />
              </div>

              <button
                @click="searchFiles()"
                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-95"
              >
                Search
              </button>
            </div>

            <!-- Compact Collapsible Filter Tray -->
            <div
              v-if="showFilters"
              class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs"
            >
              <div>
                <label class="block text-slate-400 mb-1">Folder</label>
                <select
                  v-model="folderId"
                  class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 focus:outline-none focus:border-blue-500"
                >
                  <option value="">All Folders</option>
                  <option v-for="folder in folders" :key="folder.id" :value="folder.id">
                    {{ folder.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-slate-400 mb-1">From Date</label>
                <input
                  v-model="from"
                  type="date"
                  class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 focus:outline-none focus:border-blue-500"
                />
              </div>

              <div>
                <label class="block text-slate-400 mb-1">To Date</label>
                <input
                  v-model="to"
                  type="date"
                  class="w-full bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1.5 text-slate-300 focus:outline-none focus:border-blue-500"
                />
              </div>

              <div class="sm:col-span-3 flex justify-end gap-2 pt-1 border-t border-slate-800/50">
                <button @click="clearFilters" class="text-slate-500 hover:text-slate-300 underline text-[11px]">
                  Reset Filters
                </button>
                <button @click="searchFiles()" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px]">
                  Apply Filters
                </button>
              </div>
            </div>
          </section>

          <!-- Files Table View -->
          <section class="bg-slate-900/40 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-900/90 border-b border-slate-800/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Size</th>
                    <th class="px-5 py-3">Modified</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/50 text-xs">
                  <tr
                    v-for="file in files"
                    :key="file.id"
                    class="hover:bg-slate-800/30 transition-colors group"
                  >
                    <td class="px-5 py-3.5 font-medium text-slate-200 max-w-xs truncate">
                      {{ file.name }}
                    </td>

                    <td class="px-5 py-3.5">
                      <span class="px-2 py-0.5 rounded bg-slate-800/80 text-slate-400 font-mono text-[10px] border border-slate-700/50">
                        {{ file.mimeType.split("/").pop() || file.mimeType }}
                      </span>
                    </td>

                    <td class="px-5 py-3.5 font-mono text-slate-400">
                      {{ formatSize(file.size) }}
                    </td>

                    <td class="px-5 py-3.5 text-slate-400">
                      {{ formatDate(file.modifiedTime) }}
                    </td>

                    <td class="px-5 py-3.5 text-right">
                      <div class="flex justify-end gap-2">
                        <button
                          v-if="file.canDownload !== false"
                          @click="downloadFile(file)"
                          class="px-2.5 py-1 text-[11px] font-medium text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-lg transition-all"
                        >
                          Download
                        </button>

                        <button
                          v-if="file.canDelete !== false"
                          @click="deleteFile(file)"
                          class="px-2.5 py-1 text-[11px] font-medium text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-lg transition-all"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="files.length === 0">
                    <td colspan="5" class="text-center py-12 text-slate-500">
                      No assets found matching your query.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Modern Cursor Pagination -->
            <div class="px-5 py-3.5 border-t border-slate-800/80 flex items-center justify-between bg-slate-900/60 text-xs">
              <span class="text-slate-400">
                Showing maximum <strong class="text-slate-200">{{ perPage }}</strong> items per page
              </span>

              <div class="flex items-center gap-2">
                <button
                  v-if="pagination?.nextPageToken"
                  @click="searchFiles(pagination.nextPageToken)"
                  class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium transition-all shadow-md shadow-blue-600/20 active:scale-95"
                >
                  Next Page →
                </button>
              </div>
            </div>
          </section>
        </template>

        <!-- ================= PANEL 2: UPLOAD CENTER ================= -->
        <template v-else-if="activeTab === 'upload'">
          <section class="bg-slate-900/40 border border-slate-800/80 p-6 rounded-2xl max-w-2xl mx-auto space-y-5">
            <div>
              <h3 class="font-bold text-slate-100 text-base">Upload Asset</h3>
              <p class="text-xs text-slate-400">Select a folder and upload your files directly to Google Drive.</p>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1.5">Destination Folder</label>
              <select
                v-model="folderId"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500"
              >
                <option value="">Root Directory</option>
                <option v-for="folder in folders" :key="folder.id" :value="folder.id">
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
                'border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer flex flex-col items-center justify-center min-h-[180px]',
                isDragging
                  ? 'border-blue-500 bg-blue-500/10'
                  : selectedFile
                  ? 'border-emerald-500/50 bg-emerald-500/5'
                  : 'border-slate-800 bg-slate-950/50 hover:border-slate-700',
              ]"
            >
              <input ref="fileInputRef" type="file" @change="chooseFile" class="hidden" />

              <div v-if="!selectedFile" class="space-y-2">
                <div class="w-12 h-12 rounded-xl bg-slate-800 text-blue-400 flex items-center justify-center text-xl mx-auto border border-slate-700">
                  ☁️
                </div>
                <p class="text-xs text-slate-300">
                  <span class="text-blue-400 underline font-medium">Click to upload</span> or drag files here
                </p>
              </div>

              <div v-else class="flex items-center justify-between w-full bg-slate-900 p-3 rounded-xl border border-slate-800">
                <div class="text-left truncate">
                  <p class="text-xs font-medium text-slate-200 truncate">{{ selectedFile.name }}</p>
                  <p class="text-[10px] text-slate-500">{{ formatSize(selectedFile.size) }}</p>
                </div>
                <button @click.stop="removeSelectedFile" class="text-xs text-rose-400 hover:underline">Clear</button>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                @click="uploadFile"
                :disabled="uploading || !selectedFile"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs rounded-xl transition-all disabled:opacity-50"
              >
                {{ uploading ? "Uploading..." : "Start Upload" }}
              </button>
            </div>
          </section>
        </template>
      </main>
    </div>
  </div>
</template>