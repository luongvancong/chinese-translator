import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"
import Alpine from 'alpinejs'
import axios from 'axios';

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
                .then(() => {
                    Toastify({
                        text: "Add vietpharse successfully",
                        backgroundColor: '#28a745'
                    }).showToast();

                    this.aChinese = ''
                    this.aMeaning = ''
                    this.aType = null
                })
                .catch((error) => {
                    Toastify({
                        text: error.response.data.message || error.message || "Error",
                        backgroundColor: "red"
                    }).showToast();
                })
        }
    }
})

window.Alpine = Alpine

Alpine.start()
