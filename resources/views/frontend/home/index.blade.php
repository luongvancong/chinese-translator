<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chinese translator</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/axios@1.1.2/dist/axios.min.js"></script>
    <script src="//unpkg.com/alpinejs" defer></script>

    @vite('resources/css/frontend/app.css')
    @vite('resources/js/frontend/app.js')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => {
                return {
                    chinese: '',
                    translatedContent: '',
                    aChinese: '',
                    aMeaning: '',
                    aType: null,

                    init() {

                    },

                    handleSubmitTranslate(e) {
                        e.preventDefault();
                        axios({
                            url: '/',
                            method: 'POST',
                            data: {
                                chinese: this.chinese
                            }
                        })
                        .then(response => {
                            this.translatedContent = response.data.translatedContent
                        })
                        .catch((error) => {
                            console.log(error)
                        })
                    },

                    handleSubmitAddWord(e) {
                        e.preventDefault();
                        axios({
                            url: '/add-words',
                            method: 'POST',
                            data: {
                                chinese: this.aChinese,
                                meaning: this.aMeaning,
                                type: this.aType || null
                            }
                        })
                            .then((response) => {
                                alert(response.data.message)
                                this.aChinese = ''
                                this.aMeaning = ''
                                this.aType = null
                            })
                            .catch((error) => {
                                alert(error.response.data.message || error.message || "Error")
                            })
                    }
                }
            })
        })
    </script>


</head>




<body x-data="app">
    <div class="container mx-auto px-4 py-4">
        {!! csrf_field() !!}
        <h1 class="text-3xl flex justify-center uppercase mb-2">Chinese to Vietnamese translator</h1>

        <div class="grid grid-cols-2 my-4 gap-5">
            <div>
                <div class="w-full text-xl">Chinese</div>
                <div
                    contenteditable
                    x-ref="chinese"
                    @input="chinese = $event.target.innerHTML"
                    class="w-full border border-blue-300 rounded resize-none h-[400px] px-4 py-4 overflow-auto"></div>
            </div>
            <div>
                <div class="w-full text-xl">Vietnamese</div>
                <div
                    contenteditable
                    x-html="translatedContent"
                    class="border border-pink-300 rounded h-[400px] px-4 py-4 overflow-auto"></div>
            </div>
        </div>

        <button
            x-bind:disabled="!chinese"
            @click="handleSubmitTranslate"
            type="submit"
            class="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black">Translate</button>

        <div class="border border-violet-300 rounded px-4 py-4 mt-4 mb-4">
            <h1 class="text-3xl flex justify-center uppercase mb-2">Update vietphrase</h1>
            <div class="grid grid-cols-3 my-4 gap-5">
                <div>
                    <label for="update-chinese">Chinese</label>
                    <input
                        id="update-chinese"
                        class="border rounded px-2 py-2 w-full"
                        placeholder="Chinese"
                        x-model="aChinese"
                    />
                </div>
                <div>
                    <label for="update-vietnamese">Vietnamese</label>
                    <input
                        id="update-vietnamese"
                        class="border rounded px-2 py-2 w-full"
                        placeholder="Vietnamese"
                        x-model="aMeaning"
                    />
                </div>
                <div>
                    <label for="update-vietnamese">Type</label>
                    <select
                        id="update-type"
                        class="border rounded px-2 py-2 w-full"
                        x-model="aType"
                    >
                        <option value=""></option>
                        <option value="NAME">NAME</option>
                    </select>
                </div>
            </div>
            <button
                @click="handleSubmitAddWord"
                type="submit"
                class="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black">Add VietPhrase</button>
        </div>
    </div>
</body>
</html>
