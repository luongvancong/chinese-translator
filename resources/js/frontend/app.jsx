import React, {useState} from 'react'
import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"
import axios from 'axios';
import {Input, Modal} from "antd";
import lodash from 'lodash'

export const App = () => {
    const [chinese, setChinese] = useState("")
    const [translateArr, setTranslateArr] = useState([])
    const [formAddPhrase, setFormAddPhrase] = useState({
        chinese: "",
        meaning: "",
        type: ""
    })

    const [isShowModalTranslatedContent, setIsShowModalTranslatedContent] = useState(false)
    const [sinoWordSelected, setSinoWordSelected] = useState({
        rowIndex: -1,
        wordIndex: -1
    })

    const handleChangeInputSource = (content) => {
        setChinese(content)
    }

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
        saveAddPhrase(formAddPhrase.chinese, formAddPhrase.meaning, formAddPhrase.type)
            .then(() => {
                Toastify({
                    text: "Add vietpharse successfully",
                    backgroundColor: '#28a745'
                }).showToast();

                setFormAddPhrase({
                    chinese: "",
                    meaning: "",
                    type: ""
                })
            })
            .catch((error) => {
                Toastify({
                    text: error.response.data.message || error.message || "Error",
                    backgroundColor: "red"
                }).showToast();
            })
    }

    /**
     * Update nguyên một câu văn
     * @param e
     * @param row
     */
    const handleUpdatePhrase = (e, row) => {
        saveAddPhrase(row.source, e.target.value, 'PHRASE')
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

    const handleChangeTranslateLine = (index, value) => {
        const foundIndex = translateArr.findIndex((x, i) => i === index)
        if (foundIndex >= 0) {
            translateArr[foundIndex].predict = value
            setTranslateArr([...translateArr])
        }
    }

    const handleViewTranslatedContent = () => {
        setIsShowModalTranslatedContent(true)
    }

    const handleClickSinoWord = (rowIndex, wordIndex) => {
        if (sinoWordSelected.rowIndex === rowIndex && sinoWordSelected.wordIndex === wordIndex) {
            setSinoWordSelected({
                rowIndex: -1,
                wordIndex: -1
            })
        }
        else {
            setSinoWordSelected({
                rowIndex,
                wordIndex
            })
        }
    }

    const parseSinoToken = (text) => {
        const arr = text.split(' ')
        const token = []

        const regex = /[0-9]+/gm;

        for (let i = 0; i < arr.length; i ++) {
            const chars = [',', '？', '；', ';', '?', '、', '《', '》']
            let hasSpecialChars = false
            chars.forEach((c) => {
                if (arr[i].indexOf(c) >= 0) {
                    hasSpecialChars = true
                    const arr1 = arr[i].split(c)
                    for (let j = 0; j < arr1.length; j ++) {
                        if (arr1[j] === '') {
                            arr1[j] = c
                        }
                        token.push(arr1[j])
                    }
                }
            })

            if (!hasSpecialChars) {
                token.push(arr[i])
            }
        }

        return token
    }

    const parseChineseToken = (text) => {
        const arr = text.split('')
        const token = []
        for (let i = 0; i < arr.length; i ++) {
            if (arr[i].length > 1) {
                const arr1 = arr[i].split('')
                for (let j = 0; j < arr1.length; j ++) {
                    token.push(arr1[j])
                }
            }
            else {
                token.push(arr[i])
            }
        }

        return token
    }

    const renderChinese = (rowIndex, sinoTokens) => {
        return sinoTokens
            .map((xs, xsi) =>
                <span
                    key={`c-${rowIndex}-${xsi}`}
                    onClick={() => handleClickSinoWord(rowIndex, xsi)}
                    data-row-index={rowIndex}
                    data-word-index={xsi}
                    className={`cursor-pointer inline-block rounded text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>
                    {lodash.map(xs, (v, k) => k)}</span>
            )
    }

    const renderSino = (rowIndex, sinoTokens) => {
        return sinoTokens.map((xs, xsi) => {
            const text = lodash.map(xs, v => v)
            return (
                <span
                    key={`sino-${rowIndex}-${xsi}`}
                    data-row-index={rowIndex}
                    data-word-index={xsi}
                    onClick={() => handleClickSinoWord(rowIndex, xsi)}
                    className={`cursor-pointer text-xl inline-block ${[',', '？', '?', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'].indexOf(text[0]) < 0 ? 'px-[2px]' : ''} text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>
                {text[0]}</span>
            )
        })
    }

    return (
        <div className="mx-auto px-4 py-4">

            <div className={'fixed top-0 left-0 z-50'}>
                <div className="bg-white">
                    <h1 className="text-3xl flex justify-center uppercase mb-2">Chinese to Vietnamese translator</h1>
                    <div className="grid grid-cols-2 gap-5 p-4">
                        <div className={'border border-violet-300 rounded px-4 py-4'}>
                            <div className="w-full text-xl">Đoạn văn chữ Hán</div>
                            <textarea
                                value={chinese}
                                onChange={e => handleChangeInputSource(e.target.value.replaceAll("\n", ""))}
                                className="w-full border border-blue-300 rounded resize-none h-[150px] px-4 py-4 overflow-auto mb-4" />

                            <div className="flex justify-between">
                                <div>
                                    <button
                                        onClick={handleTranslate}
                                        className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black mr-4">
                                        Dịch</button>

                                    {translateArr.length > 0 && (
                                        <button
                                            onClick={handleViewTranslatedContent}
                                            className={'bg-red-500 text-white text-xl rounded py-2 px-5'}>Xem bản dịch</button>
                                    )}
                                </div>
                                <div>
                                    <span className={'text-sm'}>{chinese.length} chữ | </span>
                                    <span className={'text-sm'}>{translateArr.length} câu</span>
                                </div>
                            </div>

                        </div>
                        <div className="border border-violet-300 rounded px-4 py-4">
                            <h1 className="text-3xl flex justify-center uppercase mb-2">Thêm từ điển</h1>
                            <div className="grid grid-cols-3 my-4 gap-5">
                                <div>
                                    <label htmlFor="update-chinese">Chinese <b className={'bold text-red-500'}>*</b></label>
                                    <input
                                        id="update-chinese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Chinese"
                                        value={formAddPhrase.chinese}
                                        onChange={e => handleChangedFormAddPhrase('chinese', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="update-vietnamese">Vietnamese <b className={'bold text-red-500'}>*</b></label>
                                    <input
                                        id="update-vietnamese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Vietnamese"
                                        value={formAddPhrase.meaning}
                                        onChange={e => handleChangedFormAddPhrase('meaning', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="update-vietnamese">Loại</label>
                                    <select
                                        id="update-type"
                                        className="border rounded px-2 py-2 w-full"
                                        value={formAddPhrase.type}
                                        onChange={e => handleChangedFormAddPhrase('type', e.target.value)}
                                    >
                                        <option value="">--</option>
                                        <option value="NAME">Tên nhân vật</option>
                                    </select>
                                </div>
                            </div>
                            <button
                                onClick={handleAddPhrase}
                                className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black"
                            >Thêm từ điển
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="my-4 bg-red mt-[400px]">
                <div className={'grid grid-cols-1 gap-5'}>
                    <div className={'col-span-2'}>
                        {translateArr.map((x, i) => (
                            <div className={'w-full mb-4'} key={i}>
                                <div className={'text-3xl'}>
                                    <span className={'rounded bg-green-200 p-2 text-xs'}>{i+1}</span>
                                    {renderChinese(i, x.sino_tokens)}
                                </div>
                                <div className={'text-md text-blue-600'}>
                                    {renderSino(i, x.sino_tokens)}
                                </div>
                                <Input.TextArea
                                    value={x.predict}
                                    onChange={e => handleChangeTranslateLine(i, e.target.value)}
                                    onPressEnter={e => handleUpdatePhrase(e, x)}
                                    className={'border rounded border-[1px] border-grey-300 p-2 w-full bg-yellow-200'} />
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <Modal
                width={800}
                title={'Bản dịch'}
                open={isShowModalTranslatedContent}
                footer={null}
                onCancel={() => setIsShowModalTranslatedContent(false)}
            >
                {translateArr.map((x, i) => (
                    <p key={i} className={'mb-2'}>{x.predict}.</p>
                ))}
            </Modal>
        </div>
    )
}


