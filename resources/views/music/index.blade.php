<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sonic Scout - Music Recognition</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lamejs@1.2.0/lame.min.js"></script>

</head>

<body class="bg-gray-900 text-white">
    <div id="app" class="min-h-screen">
        <nav class="bg-gray-800 border-b border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl font-bold text-purple-500">Sonic Scout</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="flex flex-col items-center justify-center">
                    <h1 class="text-4xl font-bold mb-8 text-center">
                        Discover Your Music
                        <div class="text-lg font-normal text-gray-400 mt-2">
                            Upload audio or record to find the song
                        </div>
                    </h1>

                    <div class="w-full max-w-md">
                        <div class="bg-gray-800 p-6 rounded-lg shadow-xl border border-gray-700">
                            <div class="space-y-4">
                                <div class="flex justify-center">
                                    <button @click="startRecording" v-if="!isRecording"
                                        class="px-4 py-2 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition duration-200">
                                        Start Recording
                                    </button>
                                    <button @click="stopRecording" v-else
                                        class="px-4 py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition duration-200 animate-pulse">
                                        Stop Recording
                                    </button>
                                </div>

                                <div class="flex items-center justify-center w-full">
                                    <label
                                        class="flex flex-col w-full h-32 border-2 border-purple-400 border-dashed hover:bg-gray-700 hover:border-purple-500 rounded-lg">
                                        <div class="flex flex-col items-center justify-center pt-7">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                                </path>
                                            </svg>
                                            <p
                                                class="pt-1 text-sm tracking-wider text-gray-400 group-hover:text-gray-100">
                                                Upload audio file
                                            </p>
                                        </div>
                                        <input type="file" class="opacity-0" @change="handleFileUpload"
                                            accept="audio/*" />
                                    </label>
                                </div>
                            </div>

                            <div v-if="isLoading" class="mt-4">
                                <div class="flex justify-center items-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                                </div>
                            </div>

                            <div v-if="result" class="mt-6">
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <img :src="result.album_art" class="w-20 h-20 rounded-lg"
                                            v-if="result.album_art">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-semibold">@{{ result.title }}</h3>
                                            <p class="text-gray-400">@{{ result.artist }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex space-x-2">
                                        <a v-if="result.spotify_url" :href="result.spotify_url" target="_blank"
                                            class="px-4 py-2 bg-green-600 text-white rounded-full text-sm hover:bg-green-700 transition duration-200">
                                            Open in Spotify
                                        </a>
                                        <a v-if="result.apple_music_url" :href="result.apple_music_url" target="_blank"
                                            class="px-4 py-2 bg-pink-600 text-white rounded-full text-sm hover:bg-pink-700 transition duration-200">
                                            Open in Apple Music
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const {
            createApp
        } = Vue;

        createApp({
            data() {
                return {
                    isRecording: false,
                    isLoading: false,
                    result: null,
                    mediaRecorder: null,
                    audioChunks: []
                };
            },
            methods: {
                async startRecording() {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            audio: {
                                channelCount: 1,
                                sampleRate: 44100,
                                echoCancellation: true,
                                noiseSuppression: true,
                            }
                        });

                        this.mediaRecorder = new MediaRecorder(stream);
                        this.audioChunks = [];

                        this.mediaRecorder.ondataavailable = (event) => {
                            this.audioChunks.push(event.data);
                        };

                        this.mediaRecorder.onstop = async () => {
                            console.log("Recording stopped"); // Tambahkan log ini
                            const audioBlob = new Blob(this.audioChunks, {
                                type: 'audio/wav'
                            });
                            const mp3Blob = await this.convertToMp3(audioBlob);
                            await this.uploadAudio(
                                mp3Blob); // Tambahkan await untuk memastikan upload selesai
                        };


                        this.mediaRecorder.start();
                        this.isRecording = true;
                    } catch (err) {
                        console.error("Error accessing microphone:", err);
                    }
                },
                stopRecording() {
                    this.mediaRecorder.stop();
                    this.isRecording = false;
                },
                async convertToMp3(audioBlob) {
                    const arrayBuffer = await audioBlob.arrayBuffer();
                    const wav = new Uint8Array(arrayBuffer);

                    const mp3Encoder = new lamejs.Mp3Encoder(1, 44100, 128);
                    const samples = new Int16Array(wav.buffer);
                    const mp3Data = [];

                    let remaining = samples.length;

                    let index = 0;
                    while (remaining >= 1152) {
                        const chunk = samples.subarray(index, index + 1152);
                        const mp3Chunk = mp3Encoder.encodeBuffer(chunk);
                        if (mp3Chunk.length > 0) {
                            mp3Data.push(new Uint8Array(mp3Chunk));
                        }
                        remaining -= 1152;
                        index += 1152;
                    }
                    const finalBuffer = mp3Encoder.flush();
                    if (finalBuffer.length > 0) {
                        mp3Data.push(new Uint8Array(finalBuffer));
                    }

                    return new Blob(mp3Data, {
                        type: 'audio/mp3'
                    });
                },
                async uploadAudio(audioBlob) {
                    this.isLoading = true;
                    this.result = null;

                    const formData = new FormData();
                    formData.append('audio_file', audioBlob, 'recording.mp3');

                    try {
                        const response = await axios.post('/recognize', formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            }
                        });

                        if (response.data.success) {
                            this.result = response.data.data;
                        } else {
                            console.error('Server response:', response.data);
                            alert('Could not recognize the song. Server response: ' + JSON.stringify(response
                                .data));
                        }
                    } catch (error) {
                        console.error('Error uploading audio:', error);
                        alert('Error uploading audio. Please try again.');
                    } finally {
                        this.isLoading = false;
                    }
                },
                handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = async (e) => {
                            const audioBlob = new Blob([new Uint8Array(e.target.result)], {
                                type: file.type
                            });
                            await this.uploadAudio(audioBlob);
                        };
                        reader.readAsArrayBuffer(file);
                    }
                }
            }
        }).mount('#app');
    </script>
</body>

</html>
