<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

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
  };
}>();

const from = ref(props.filters.from ?? "");
const to = ref(props.filters.to ?? "");
const search = ref(props.filters.search ?? "");
const folderId = ref(props.filters.folder_id ?? "");

const selectedFile = ref<File | null>(null);
const uploading = ref(false);
function logout() {
  router.post("/logout");
}
function searchFiles() {
  router.get(
    "/drive",
    {
      from: from.value || undefined,
      to: to.value || undefined,
      search: search.value || undefined,
      folder_id: folderId.value || undefined,
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

  router.get("/drive");
}

function downloadFile(file: DriveFile) {
  window.location.href = `/drive/${file.id}/download`;
}

function deleteFile(file: DriveFile) {
  if (!confirm(`Are you sure you want to delete "${file.name}"?`)) {
    return;
  }

  router.delete(`/drive/${file.id}`, {
    preserveScroll: true,
  });
}

function chooseFile(event: Event) {
  const target = event.target as HTMLInputElement;

  selectedFile.value = target.files?.[0] ?? null;
}

function uploadFile() {
  if (!selectedFile.value) {
    alert("Please select a file.");
    return;
  }

  const formData = new FormData();

  formData.append("file", selectedFile.value);

  if (folderId.value) {
    formData.append("folder_id", folderId.value);
  }

  uploading.value = true;

  router.post("/drive/upload", formData, {
    forceFormData: true,

    onFinish: () => {
      uploading.value = false;
    },

    onSuccess: () => {
      selectedFile.value = null;

      router.reload({
        only: ["files"],
      });
    },
  });
}

function formatSize(size?: string) {
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

function formatDate(date?: string) {
  if (!date) {
    return "-";
  }

  return new Date(date).toLocaleString();
}
</script>

<template>
  <Head title="Google Drive" />

  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between">
        <div>
          <h1 class="text-xl font-bold">Google Drive Manager</h1>

          <p class="text-sm text-gray-500">Google Drive Files</p>
        </div>

        <button
          @click="logout"
          class="px-4 py-2 bg-gray-800 text-white rounded-lg"
        >
          Logout
        </button>
      </div>
    </header>

    <main class="max-w-7xl mx-auto p-6">
      <!-- Filters -->
      <section class="bg-white p-5 rounded-xl shadow-sm mb-6">
        <h2 class="font-semibold mb-4">Search Files</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm mb-1"> From </label>

            <input
              v-model="from"
              type="date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> To </label>

            <input
              v-model="to"
              type="date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> File Name </label>

            <input
              v-model="search"
              type="text"
              placeholder="Search..."
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div>
            <label class="block text-sm mb-1"> Folder </label>

            <select
              v-model="folderId"
              class="w-full border rounded-lg px-3 py-2"
            >
              <option value="">All folders</option>

              <option
                v-for="folder in folders"
                :key="folder.id"
                :value="folder.id"
              >
                {{ folder.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 mt-4">
          <button
            @click="searchFiles"
            class="px-5 py-2 bg-blue-600 text-white rounded-lg"
          >
            Search
          </button>

          <button @click="clearFilters" class="px-5 py-2 border rounded-lg">
            Clear
          </button>
        </div>
      </section>

      <!-- Upload -->
      <section class="bg-white p-5 rounded-xl shadow-sm mb-6">
        <h2 class="font-semibold mb-4">Upload File</h2>

        <div class="flex flex-col md:flex-row gap-3">
          <input
            type="file"
            @change="chooseFile"
            class="border rounded-lg p-2"
          />

          <button
            @click="uploadFile"
            :disabled="uploading"
            class="px-5 py-2 bg-green-600 text-white rounded-lg disabled:opacity-50"
          >
            {{ uploading ? "Uploading..." : "Upload" }}
          </button>
        </div>

        <p v-if="selectedFile" class="text-sm text-gray-500 mt-2">
          Selected:
          {{ selectedFile.name }}
        </p>
      </section>

      <!-- Files -->
      <section class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b">
          <h2 class="font-semibold">Files ({{ files.length }})</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-5 py-3">Name</th>

                <th class="text-left px-5 py-3">Type</th>

                <th class="text-left px-5 py-3">Size</th>

                <th class="text-left px-5 py-3">Modified</th>

                <th class="text-right px-5 py-3">Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="file in files" :key="file.id" class="border-t">
                <td class="px-5 py-4 font-medium">
                  {{ file.name }}
                </td>

                <td class="px-5 py-4 text-sm text-gray-500">
                  {{ file.mimeType }}
                </td>

                <td class="px-5 py-4">
                  {{ formatSize(file.size) }}
                </td>

                <td class="px-5 py-4">
                  {{ formatDate(file.modifiedTime) }}
                </td>

                <td class="px-5 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <!-- Download -->
                    <button
                      v-if="file.canDownload !== false"
                      @click="downloadFile(file)"
                      class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
                    >
                      Download
                    </button>

                    <!-- Delete -->
                    <button
                      v-if="file.canDelete !== false"
                      @click="deleteFile(file)"
                      class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="files.length === 0">
                <td colspan="5" class="text-center py-10 text-gray-500">
                  No files found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>