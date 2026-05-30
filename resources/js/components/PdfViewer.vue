<template>
    <div class="pdf-viewer-container h-full w-full">
        <div class="pdf-toolbar flex items-center justify-between border-b border-slate-300 bg-slate-100 p-2">
            <div class="flex items-center gap-2">
                <button
                    @click="previousPage"
                    :disabled="currentPage <= 1"
                    class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-300 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Previous
                </button>
                <span class="text-sm text-slate-700">Page {{ currentPage }} of {{ totalPages }}</span>
                <button
                    @click="nextPage"
                    :disabled="currentPage >= totalPages"
                    class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-300 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Next
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button @click="zoomOut" class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-300">-</button>
                <span class="text-sm text-slate-700">{{ Math.round(scale * 100) }}%</span>
                <button @click="zoomIn" class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-300">+</button>
            </div>
        </div>
        <div class="pdf-content h-[calc(100%-3rem)] overflow-auto bg-slate-200 p-4">
            <canvas ref="pdfCanvas" class="mx-auto block bg-white shadow-lg"></canvas>
        </div>
    </div>
</template>

<script setup>
import * as pdfjsLib from 'pdfjs-dist';
import { onMounted, ref, watch } from 'vue';

// Set worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js`;

const props = defineProps({
    pdfUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['loaded', 'error']);

const pdfCanvas = ref(null);
const pdfDoc = ref(null);
const currentPage = ref(1);
const totalPages = ref(0);
const scale = ref(1.5);
const isLoading = ref(false);

const loadPdf = async () => {
    try {
        isLoading.value = true;
        const loadingTask = pdfjsLib.getDocument(props.pdfUrl);
        pdfDoc.value = await loadingTask.promise;
        totalPages.value = pdfDoc.value.numPages;
        await renderPage(currentPage.value);
        emit('loaded');
    } catch (error) {
        console.error('Error loading PDF:', error);
        emit('error', error);
    } finally {
        isLoading.value = false;
    }
};

const renderPage = async (pageNum) => {
    if (!pdfDoc.value) return;

    try {
        const page = await pdfDoc.value.getPage(pageNum);
        const viewport = page.getViewport({ scale: scale.value });
        const canvas = pdfCanvas.value;
        const context = canvas.getContext('2d');

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
            canvasContext: context,
            viewport: viewport,
        };

        await page.render(renderContext).promise;
    } catch (error) {
        console.error('Error rendering page:', error);
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
        renderPage(currentPage.value);
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
        renderPage(currentPage.value);
    }
};

const zoomIn = () => {
    scale.value += 0.25;
    renderPage(currentPage.value);
};

const zoomOut = () => {
    if (scale.value > 0.5) {
        scale.value -= 0.25;
        renderPage(currentPage.value);
    }
};

onMounted(() => {
    loadPdf();
});

watch(
    () => props.pdfUrl,
    () => {
        currentPage.value = 1;
        loadPdf();
    },
);

defineExpose({
    reload: loadPdf,
});
</script>

<style scoped>
.pdf-viewer-container {
    display: flex;
    flex-direction: column;
}
</style>
