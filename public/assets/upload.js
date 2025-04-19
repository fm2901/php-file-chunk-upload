let uploadInProgress = false;
let paused = false;
let currentChunkIndex = 0;
let totalChunks = 0;
let file = null;
let uploadId = null;
let chunkSize = 1024 * 1024; // 1 MB
let fileSrc = '';

const progressContainer = document.getElementById('progressContainer');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const loader = document.getElementById('loader');
const startButton = document.getElementById('startButton');
const pauseButton = document.getElementById('pauseButton');
const resumeButton = document.getElementById('resumeButton');
const fileLinkContainer = document.getElementById('fileLinkContainer');

document.getElementById('fileInput').addEventListener('change', handleFileSelect);

async function handleFileSelect() {
    file = document.getElementById('fileInput').files[0];
    if (!file) return;

    startButton.classList.remove('hidden');
    progressContainer.classList.add('hidden');
    loader.classList.add('hidden');
    pauseButton.classList.add('hidden');
    fileLinkContainer.classList.add('hidden')

    currentChunkIndex = 0;
    totalChunks = Math.ceil(file.size / chunkSize);
    uploadId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

    document.getElementById('startButton').classList.remove('hidden');
}

async function startUpload() {
    if (uploadInProgress || paused) return;
    console.log('startUpload')

    const fileLink = document.getElementById('fileLink');
    const fileName = file.name;
    let percent = 0;
    let responseData;

    startButton.classList.add('hidden');
    progressContainer.classList.remove('hidden');
    loader.classList.remove('hidden');
    pauseButton.classList.remove('hidden');

    uploadInProgress = true;

    for (let i = currentChunkIndex; i < totalChunks; i++) {
        if (paused) break;

        const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
        const formData = new FormData();

        formData.append('uploadId', uploadId);
        formData.append('fileName', file.name);
        formData.append('chunkIndex', i);
        formData.append('totalChunks', totalChunks);
        formData.append('chunk', chunk);

        try {
            const response = await fetch('/api/upload.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Ошибка при загрузке чанка ${i + 1}`);
            }

            responseData = await response.json();
            if (responseData.success) {
                currentChunkIndex = i + 1; // Обновляем индекс текущего чанка

                percent = Math.round(((i + 1) / totalChunks) * 100);
                progressBar.style.width = `${percent}%`;
                progressText.textContent = `${percent}%`;
            } else {
                throw new Error('Ошибка на сервере');
            }
        } catch (error) {
            alert(`Ошибка при загрузке чанка ${i + 1}`);
            console.error(error);
            return;
        }
    }

    if (percent === 100) {
        loader.classList.add('hidden');
        pauseButton.classList.add('hidden');
        fileLinkContainer.classList.remove('hidden');

        document.getElementById('fileLink').href = responseData?.fileUrl;
        document.getElementById('fileLink').textContent = fileName;
        uploadInProgress = false;
    }
}

async function pauseUpload() {
    paused = true;
    uploadInProgress = false;
    document.getElementById('pauseButton').classList.add('hidden');
    document.getElementById('resumeButton').classList.remove('hidden');
}

async function resumeUpload() {
    console.log('resumeUpload called');

    paused = false;
    uploadInProgress = false;

    document.getElementById('resumeButton').classList.add('hidden');
    document.getElementById('pauseButton').classList.remove('hidden');

    await startUpload();
}


async function copyLink() {
    const link = document.querySelector('#fileLink')?.href;
    if (link) {
        await navigator.clipboard.writeText(link);
        alert('Ссылка скопирована!');
    }
}


