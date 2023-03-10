import React, {useState} from 'react'
import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"
import axios from 'axios';
import {Input, Modal} from "antd";

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
     * Update nguy??n m???t c??u v??n
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
        for (let i = 0; i < arr.length; i ++) {
            const chars = [',', '???', '???', ';', '?', '???']
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

    const renderChinese = (rowIndex, text) => {
        return parseChineseToken(text)
            .map((xs, xsi) =>
                <span
                    onClick={() => handleClickSinoWord(rowIndex, xsi)}
                    data-row-index={rowIndex}
                    data-word-index={xsi}
                    className={`inline-block rounded text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>
                    {xs}</span>
            )
    }

    const renderSino = (rowIndex, text) => {
        return parseSinoToken(text).map((xs, xsi) =>
            <span
                onClick={() => handleClickSinoWord(rowIndex, xsi)}
                className={`text-xl inline-block ${[',', '???', '?'].indexOf(xs) < 0 ? 'px-[2px]' : ''} text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>{xs}</span>)
    }

    return (
        <div className="mx-auto px-4 py-4">

            <div className={'fixed top-0 left-0 z-50'}>
                <div className="bg-white">
                    <h1 className="text-3xl flex justify-center uppercase mb-2">Chinese to Vietnamese translator</h1>
                    <div className="grid grid-cols-2 gap-5 p-4">
                        <div className={'border border-violet-300 rounded px-4 py-4'}>
                            <div className="w-full text-xl">??o???n v??n ch??? H??n</div>
                            <textarea
                                value={chinese}
                                onChange={e => handleChangeInputSource(e.target.value.replaceAll("\n", ""))}
                                className="w-full border border-blue-300 rounded resize-none h-[150px] px-4 py-4 overflow-auto mb-4" />

                            <div className="flex justify-between">
                                <div>
                                    <button
                                        onClick={handleTranslate}
                                        className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black mr-4">
                                        D???ch</button>

                                    {translateArr.length > 0 && (
                                        <button
                                            onClick={handleViewTranslatedContent}
                                            className={'bg-red-500 text-white text-xl rounded py-2 px-5'}>Xem b???n d???ch</button>
                                    )}
                                </div>
                                <div>
                                    <span className={'text-sm'}>{chinese.length} ch??? | </span>
                                    <span className={'text-sm'}>{translateArr.length} c??u</span>
                                </div>
                            </div>

                        </div>
                        <div className="border border-violet-300 rounded px-4 py-4">
                            <h1 className="text-3xl flex justify-center uppercase mb-2">Th??m t??? ??i???n</h1>
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
                                    <label htmlFor="update-vietnamese">Lo???i</label>
                                    <select
                                        id="update-type"
                                        className="border rounded px-2 py-2 w-full"
                                        value={formAddPhrase.type}
                                        onChange={e => handleChangedFormAddPhrase('type', e.target.value)}
                                    >
                                        <option value="">--</option>
                                        <option value="NAME">T??n nh??n v???t</option>
                                    </select>
                                </div>
                            </div>
                            <button
                                onClick={handleAddPhrase}
                                className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black"
                            >Th??m t??? ??i???n
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
                                    {renderChinese(i, x.source)}
                                </div>
                                <div className={'text-md text-blue-600'}>
                                    {renderSino(i, x.sino)}
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
                title={'B???n d???ch'}
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


