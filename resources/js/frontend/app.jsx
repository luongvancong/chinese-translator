import React, {useState} from 'react'
import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"
import axios from 'axios';

export const App = () => {
    const [chinese, setChinese] = useState("")
    const [translateArr, setTranslateArr] = useState([])
    const [formAddPhrase, setFormAddPhrase] = useState({
        chinese: "",
        meaning: "",
        type: ""
    })
    const [currentTranslateLineValue, setCurrentTranslateLineValue] = useState("")

    const saveAddPhrase = (chinese, meaning, type) => {
        return axios({
            url: '/add-words?_token' + _token,
            method: 'POST',
            data: {
                chinese,
                meaning,
                type
            }
        })
    }

    const handleTranslate = (e) => {
        e.preventDefault()
        axios({
            url: `/?_token=${_token}`,
            method: 'POST',
            data: {
                chinese
            }
        })
            .then(response => {
                setTranslateArr([...response.data])
            })
            .catch((error) => {
                Toastify({
                    text: error.response.data.message || error.message || "Error",
                    backgroundColor: "red"
                }).showToast();
            })
    }

    const handleChangedFormAddPhrase = (key, value) => {
        setFormAddPhrase({
            ...formAddPhrase,
            [key]: value
        })
    }

    const handleAddPhrase = (e) => {
        e.preventDefault();
        saveAddPhrase(formAddPhrase)
            .then(() => {
                Toastify({
                    text: "Add vietpharse successfully",
                    backgroundColor: '#28a745'
                }).showToast();
            })
            .catch((error) => {
                Toastify({
                    text: error.response.data.message || error.message || "Error",
                    backgroundColor: "red"
                }).showToast();
            })
    }

    const handleUpdatePhrase = (e, row) => {
        if (e.code === "Enter") {
            saveAddPhrase(row.source, e.target.value)
                .then(() => {
                    Toastify({
                        text: "Update successfully",
                        backgroundColor: '#28a745'
                    }).showToast();
                })
                .catch((error) => {
                    Toastify({
                        text: error.response.data.message || error.message || "Error",
                        backgroundColor: "red"
                    }).showToast();
                })
        }
    }

    const handleChangeTranslateLine = (index, value) => {
        const foundIndex = translateArr.findIndex((x, i) => i === index)
        if (foundIndex >= 0) {
            translateArr[foundIndex].predict = value
            setTranslateArr([...translateArr])
        }
    }

    return (
        <div className="mx-auto px-4 py-4">

            <div className={'fixed top-0 left-0'}>
                <div className="bg-white">
                    <h1 className="text-3xl flex justify-center uppercase mb-2">Chinese to Vietnamese translator</h1>
                    <div className="grid grid-cols-2 gap-5 p-4">
                        <div className={'border border-violet-300 rounded px-4 py-4'}>
                            <div className="w-full text-xl">Raw Chinese</div>
                            <textarea
                                value={chinese}
                                onChange={e => setChinese(e.target.value.replaceAll("\n", ""))}
                                className="w-full border border-blue-300 rounded resize-none h-[150px] px-4 py-4 overflow-auto mb-4" />
                            <button
                                onClick={handleTranslate}
                                className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black mr-4">
                                Translate</button>
                        </div>
                        <div className="border border-violet-300 rounded px-4 py-4">
                            <h1 className="text-3xl flex justify-center uppercase mb-2">Update vietphrase</h1>
                            <div className="grid grid-cols-3 my-4 gap-5">
                                <div>
                                    <label htmlFor="update-chinese">Chinese</label>
                                    <input
                                        id="update-chinese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Chinese"
                                        value={formAddPhrase.chinese}
                                        onChange={e => handleChangedFormAddPhrase('chinese', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="update-vietnamese">Vietnamese</label>
                                    <input
                                        id="update-vietnamese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Vietnamese"
                                        value={formAddPhrase.meaning}
                                        onChange={e => handleChangedFormAddPhrase('meaning', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="update-vietnamese">Type</label>
                                    <select
                                        id="update-type"
                                        className="border rounded px-2 py-2 w-full"
                                        value={formAddPhrase.type}
                                        onChange={e => handleChangedFormAddPhrase('type', e.target.value)}
                                    >
                                        <option value="">--</option>
                                        <option value="NAME">NAME</option>
                                    </select>
                                </div>
                            </div>
                            <button
                                onClick={handleAddPhrase}
                                className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black"
                            >Add VietPhrase
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 my-4 bg-red mt-[400px]">
                <div>
                    {translateArr.map((x, i) => (
                        <div className={'w-full'} key={i}>
                            <div className={'text-xl'}>{x.source}</div>
                            <textarea
                                value={x.predict}
                                onChange={e => handleChangeTranslateLine(i, e.target.value)}
                                onKeyPress={e => handleUpdatePhrase(e, x)}
                                className={'border rounded border-[1px] border-grey-300 p-2 w-full bg-yellow-200'} />
                        </div>
                    ))}
                </div>
            </div>
        </div>
    )
}


