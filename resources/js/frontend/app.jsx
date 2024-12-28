import React, {useState} from 'react'
import Toastify from 'toastify-js'
import "toastify-js/src/toastify.css"
import axios from 'axios';
import {Input, Modal, Spin} from "antd";
import lodash from 'lodash'
import {sortBy} from "lodash/collection";

export const App = () => {
    const [loading, setLoading] = useState(false)
    const [chinese, setChinese] = useState("")
    const [totalLines, setTotalLines] = useState(0)
    const [totalWords, setTotalWords] = useState(0)
    const [uniqueWords, setUniqueWords] = useState(0)
    const [translateArr, setTranslateArr] = useState([])
    const [translatedLines, setTranslatedLines] = useState([])
    const [formAddPhrase, setFormAddPhrase] = useState({
        chinese: "",
        meaning: "",
        type: ""
    })

    const [isShowModalTranslatedContent, setIsShowModalTranslatedContent] = useState(false)
    const [isShowModalHanVietContent, setIsShowModalHanVietContent] = useState(false)
    const [sinoWordSelected, setSinoWordSelected] = useState({
        rowIndex: -1,
        wordIndex: -1
    })
    const [vietNameseWordSelected, setVietnameseWordSelected] = useState({
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
        setLoading(true)
        axios({
            url: `/?_token=${_token}`,
            method: 'POST',
            data: {
                chinese
            }
        })
            .then(response => {
                setLoading(false)
                // setTranslateArr([...response.data])
                setTotalLines(response.data.total_lines)
                setTotalWords(response.data.total_words)
                setUniqueWords(response.data.unique_words)

                const tempTranslateArr = []

                const data = response.data.data
                for (let i = 0; i < data.length; i++) {
                    let tempChinese = []
                    const line = data[i]
                    const phrase = line.phrase
                    const phraseTokens = line.phrase_tokens
                    const nameTokens = line.name_tokens
                    const wordTokens = line.word_tokens
                    const delimiterIndexes = line.delimiter_indexes

                    const tokenList = [phraseTokens, nameTokens, wordTokens]
                    for (let tokens of tokenList) {
                        for(let token of tokens) {
                            const indexes = token.indexes
                            if (indexes.length) {
                                for (let index of indexes) {
                                    tempChinese.push({
                                        index,
                                        original: token.original,
                                        meaning: token.meaning,
                                        sino: token.sino
                                    })
                                }
                            }

                        }
                    }

                    for (let x of delimiterIndexes) {
                        for (let index of x.indexes) {
                            tempChinese.push({
                                index,
                                original: x.original,
                                meaning: x.meaning,
                                sino: x.sino
                            })
                        }
                    }

                    tempTranslateArr[i] = sortBy(tempChinese, x => x.index)
                    console.log(tempTranslateArr)
                }

                setTranslateArr([...tempTranslateArr])

                setTranslatedLines(() => {
                    return tempTranslateArr.map((x, i) => {
                        return {
                            original: x.map(y => y.original).join(''),
                            meaning: x.map(y => y.meaning).join(' '),
                            sino: x.map(y => y.sino).join(' ')
                        }
                    })
                })

            })
            .catch((error) => {
                setLoading(false)
                console.log(error)
                Toastify({
                    text: error.response.data.message || error.message || "Error",
                    backgroundColor: "red"
                }).showToast();
            })
    }

    const handleChangedFormAddPhrase = (key, value) => {
        let newValue = value
        if (key === 'chinese') {
            newValue = value ? value.trim().replaceAll(/\s/g, "") : ""
        }
        setFormAddPhrase({
            ...formAddPhrase,
            [key]: newValue
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
     * @param line
     */
    const handleUpdatePhrase = (line) => {
        saveAddPhrase(translatedLines[line].original, translatedLines[line].meaning, 'PHRASE')
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
        translatedLines[index].meaning = value
        setTranslatedLines([...translatedLines])
    }

    const handleViewTranslatedContent = () => {
        setIsShowModalTranslatedContent(true)
    }

    const handleViewHanVietContent = () => {
        setIsShowModalHanVietContent(true)
    }

    const handleClickSinoWord = (rowIndex, wordIndex) => {
        // Reset selected
        setSinoWordSelected({
            rowIndex: -1,
            wordIndex: -1
        })

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

    const renderChinese = (rowIndex, tokens) => {
        function renderClassName (rowIndex, xsi) {
            if (rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex) {
                return 'bg-yellow-300 rounded'
            }

            if (rowIndex === vietNameseWordSelected.rowIndex && xsi === vietNameseWordSelected.wordIndex) {
                return 'bg-yellow-300 rounded'
            }

            return ''
        }

        // console.log(tokens)

        return tokens
            .map((xs, xsi) => {
                return (
                    <span
                        key={`c-${rowIndex}-${xsi}`}
                        onClick={() => handleClickSinoWord(rowIndex, xsi)}
                        data-row-index={rowIndex}
                        data-word-index={xsi}
                        className={`cursor-pointer border-[1px] border-transparent hover:border-blue-400 inline-block rounded text-left ${renderClassName(rowIndex, xsi)}`}>
                    {xs.original}</span>
                )
            })
    }

    const renderSino = (rowIndex, tokens) => {
        return tokens.map((xs, xsi) => {
            const text = lodash.map(xs, v => v)
            return (
                <span
                    key={`sino-${rowIndex}-${xsi}`}
                    data-row-index={rowIndex}
                    data-word-index={xsi}
                    onClick={() => handleClickSinoWord(rowIndex, xsi)}
                    className={`cursor-pointer text-xl inline-block ${[',', '？', '?', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'].indexOf(text[0]) < 0 ? 'px-[2px]' : ''} text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>
                {xs.sino}</span>
            )
        })
    }

    const renderVietnamese = (rowIndex, tokens) => {
        return tokens.map((xs, xsi) => {
            const text = lodash.map(xs, v => v)
            return (
                <span
                    key={`sino-${rowIndex}-${xsi}`}
                    data-row-index={rowIndex}
                    data-word-index={xsi}
                    onClick={() => handleClickSinoWord(rowIndex, xsi)}
                    className={`cursor-pointer border-[1px] border-transparent hover:border-red-400 text-xl inline-block ${[',', '？', '?', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'].indexOf(text[0]) < 0 ? 'px-[2px]' : ''} text-left ${rowIndex === sinoWordSelected.rowIndex && xsi === sinoWordSelected.wordIndex ? 'bg-yellow-300 rounded' : ''}`}>
                {xs.meaning}</span>
            )
        })
    }

    return (
        <div className="mx-auto px-4 py-4">

            <div className={'md:fixed md:top-0 md:left-0 z-50'}>
                <div className="bg-white">
                    <h1 className="text-3xl flex justify-center uppercase mb-2">Chinese to Vietnamese translator</h1>
                    <div className="grid grid-cols-2 gap-5 p-4">
                        <div className={'col-span-2 md:col-span-1 border border-violet-300 rounded px-4 py-4'}>
                            <div className="w-full text-xl">Đoạn văn chữ Hán</div>
                            <textarea
                                value={chinese}
                                onChange={e => handleChangeInputSource(e.target.value.replaceAll("\n", ""))}
                                className="w-full border border-blue-300 rounded resize-none h-[150px] px-4 py-4 overflow-auto mb-4" />

                            <div className="flex flex-col gap-3 md:justify-between">
                                <div className={'flex gap-3'}>
                                    <button
                                        onClick={handleTranslate}
                                        className="rounded bg-blue-500 text-white uppercase text-xl py-2 px-5 disabled:bg-gray-500 disabled:text-black">
                                        Dịch</button>

                                    {translateArr.length > 0 && (
                                        <>
                                            <button
                                                onClick={handleViewTranslatedContent}
                                                className={'bg-red-500 text-white text-xl rounded py-2 px-5'}>Xem bản
                                                dịch
                                            </button>
                                            <button
                                                onClick={handleViewHanVietContent}
                                                className={'bg-blue-500 text-white text-xl rounded py-2 px-5'}>Xem hán việt</button>
                                        </>
                                    )}
                                </div>
                                <div>
                                    <span className={'text-sm'}>{totalWords} chữ | </span>
                                    <span className={'text-sm'}>{uniqueWords} từ | </span>
                                    <span className={'text-sm'}>{totalLines} câu</span>
                                </div>
                            </div>

                        </div>
                        <div className="col-span-2 md:col-span-1 border border-violet-300 rounded px-4 py-4">
                            <h1 className="text-3xl flex justify-center uppercase mb-2">Thêm từ điển</h1>
                            <div className="grid grid-cols-3 my-4 gap-5">
                                <div className={'col-span-3 md:col-span-1'}>
                                    <label htmlFor="update-chinese">Chinese <b className={'bold text-red-500'}>*</b></label>
                                    <input
                                        id="update-chinese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Chinese"
                                        value={formAddPhrase.chinese.replaceAll(/\s/g, "")}
                                        onChange={e => handleChangedFormAddPhrase('chinese', e.target.value)}
                                    />
                                </div>
                                <div className={'col-span-3 md:col-span-1'}>
                                    <label htmlFor="update-vietnamese">Vietnamese <b className={'bold text-red-500'}>*</b></label>
                                    <input
                                        id="update-vietnamese"
                                        className="border rounded px-2 py-2 w-full"
                                        placeholder="Vietnamese"
                                        value={formAddPhrase.meaning}
                                        onChange={e => handleChangedFormAddPhrase('meaning', e.target.value)}
                                    />
                                </div>
                                <div className={'col-span-3 md:col-span-1'}>
                                    <label htmlFor="update-vietnamese">Loại</label>
                                    <select
                                        id="update-type"
                                        className="border rounded px-2 py-2 w-full"
                                        value={formAddPhrase.type}
                                        onChange={e => handleChangedFormAddPhrase('type', e.target.value)}
                                    >
                                        <option value="">--</option>
                                        <option value="NAME">Tên nhân vật</option>
                                        <option value="PHRASE">Đoạn văn</option>
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

            <Spin spinning={loading}>
                <div className="my-4 bg-red md:mt-[400px]">
                    <div className={'grid grid-cols-1 gap-5'}>
                        <div className={'col-span-2'}>
                            {translateArr.map((x, i) => (
                                <div className={'w-full mb-4'} key={i}>
                                    {/*{x.predict}*/}
                                    <div className={'text-3xl flex flex-wrap'}>
                                        <span className={'rounded bg-green-200 p-2 text-xs'}>{i + 1}</span>
                                        {renderChinese(i, x)}
                                    </div>
                                    <div className={'text-md text-red-600 flex flex-wrap'}>
                                        {renderSino(i, x)}
                                    </div>
                                    <div className={'text-lg text-blue-600 flex flex-wrap'}>
                                        {renderVietnamese(i, x)}
                                    </div>
                                    <Input
                                        spellCheck={false}
                                        value={translatedLines[i].meaning}
                                        onChange={e => handleChangeTranslateLine(i, e.target.value)}
                                        onPressEnter={() => handleUpdatePhrase(i)}
                                        className={'border rounded border-[1px] border-grey-300 p-2 w-full bg-yellow-200'} />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </Spin>


            <Modal
                width={800}
                title={'Bản dịch'}
                open={isShowModalTranslatedContent}
                footer={null}
                onCancel={() => setIsShowModalTranslatedContent(false)}
            >
                {translatedLines.map((x, i) => (
                    <p key={i} className={'mb-2'}>{x.meaning}.</p>
                ))}
            </Modal>

            <Modal
                width={800}
                title={'Bản dịch hán việt'}
                open={isShowModalHanVietContent}
                footer={null}
                onCancel={() => setIsShowModalHanVietContent(false)}
            >
                {translatedLines.map((x, i) => (
                    <p key={i} className={'mb-2'}>{x.sino}.</p>
                ))}
            </Modal>
        </div>
    )
}


