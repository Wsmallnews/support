import * as FilePond from 'filepond'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'

FilePond.registerPlugin(FilePondPluginImagePreview)

window.FilePond = FilePond

export default function supportFileUploadComponent({
    
    acceptedFileTypes,
    getUploadedFilesUsing,
    uploadUsing,
    deleteUploadedFileUsing,
    removeUploadedFileUsing,
    reorderUploadedFilesUsing,

    isAvatar,
    isDeletable,
    isDisabled,
    isDownloadable,
    isMultiple,
    isOpenable,
    isPreviewable,
    isReorderable,

    imagePreviewHeight,

    locale,
    shouldAppendFiles,
    shouldOrientImageFromExif,

    maxFiles,
    maxSize,
    minSize,

    state,

    uploadButtonPosition,
    uploadingMessage,
    uploadProgressIndicatorPosition,
    removeUploadedFileButtonPosition
}) {
    return {
        fileKeyIndex: {},
        uploadedFileIndex: {},
        shouldUpdateState: true,
        state,
        init: async function () {

            FilePond.setOptions(locales[locale] ?? locales['en'])

            this.pond = FilePond.create(this.$refs.input, {
                acceptedFileTypes,
                credits: false,             // Shows Powered by PQINA in footer
                files: await this.getFiles(),
                allowImageExifOrientation: shouldOrientImageFromExif,
                allowPaste: false,          // 默认禁用 粘贴文件上传
                allowRemove: isDeletable,       // 删除按钮
                allowReorder: isReorderable,        // 多图上传文件排序
                allowImagePreview: isPreviewable,   // 预览插件
                allowVideoPreview: isPreviewable,
                allowAudioPreview: isPreviewable,

                imagePreviewHeight,

                itemInsertLocation: shouldAppendFiles ? 'after' : 'before',

                maxFiles,
                maxFileSize: maxSize,
                minFileSize: minSize,


                // 待研究参数

                // allowImageTransform: shouldTransformImage,
                // imageResizeTargetHeight,
                // imageResizeTargetWidth,
                // imageResizeMode,
                // imageResizeUpscale,
                // ...(placeholder && { labelIdle: placeholder }),

                styleButtonProcessItemPosition: uploadButtonPosition,
                styleButtonRemoveItemPosition: removeUploadedFileButtonPosition,

                // styleItemPanelAspectRatio: itemPanelAspectRatio,
                // styleLoadIndicatorPosition: loadingIndicatorPosition,
                // stylePanelAspectRatio: panelAspectRatio,
                // stylePanelLayout: panelLayout,
                styleProgressIndicatorPosition: uploadProgressIndicatorPosition,






                server: {
                    load: async (source, load) => {
                        let response = await fetch(source, {
                            cache: 'no-store',
                        })
                        let blob = await response.blob()

                        load(blob)
                    },
                    process: (
                        fieldName,
                        file,
                        metadata,
                        load,
                        error,
                        progress,
                    ) => {
                        this.shouldUpdateState = false

                        let fileKey = (
                            [1e7] +
                            -1e3 +
                            -4e3 +
                            -8e3 +
                            -1e11
                        ).replace(/[018]/g, (c) =>
                            (
                                c ^
                                (crypto.getRandomValues(new Uint8Array(1))[0] &
                                    (15 >> (c / 4)))
                            ).toString(16),
                        )

                        uploadUsing(
                            fileKey,
                            file,
                            (fileKey) => {
                                this.shouldUpdateState = true

                                load(fileKey)
                            },
                            error,
                            progress,
                        )
                    },
                    remove: async (source, load) => {
                        let fileKey = this.uploadedFileIndex[source] ?? null

                        if (!fileKey) {
                            return
                        }

                        await deleteUploadedFileUsing(fileKey)

                        load()
                    },
                    revert: async (uniqueFileId, load) => {
                        await removeUploadedFileUsing(uniqueFileId)

                        load()
                    },
                },
                fileValidateTypeDetectType: (source, detectedType) => {
                    return new Promise((resolve, reject) => {
                        const mimeType =
                            detectedType ||
                            mime.getType(source.name.split('.').pop())
                        mimeType ? resolve(mimeType) : reject()
                    })
                },
            })

            this.$watch('state', async () => {
                if (!this.pond) {
                    return
                }

                if (!this.shouldUpdateState) {
                    return
                }

                if (this.state === undefined) {
                    return
                }

                // We don't want to overwrite the files that are already in the input, if they haven't been saved yet.
                if (
                    this.state !== null &&
                    Object.values(this.state).filter((file) =>
                        file.startsWith('livewire-file:'),
                    ).length
                ) {
                    this.lastState = null

                    return
                }

                // Don't do anything if the state hasn't changed
                if (JSON.stringify(this.state) === this.lastState) {
                    return
                }

                this.lastState = JSON.stringify(this.state)

                this.pond.files = await this.getFiles()
            })

            this.pond.on('reorderfiles', async (files) => {
                const orderedFileKeys = files
                    .map((file) =>
                        file.source instanceof File
                            ? file.serverId
                            : this.uploadedFileIndex[file.source] ?? null,
                    ) // file.serverId is null for a file that is not yet uploaded
                    .filter((fileKey) => fileKey)

                await reorderUploadedFilesUsing(
                    shouldAppendFiles
                        ? orderedFileKeys
                        : orderedFileKeys.reverse(),
                )
            })

            this.pond.on('initfile', async (fileItem) => {
                if (!isDownloadable) {
                    return
                }

                if (isAvatar) {
                    return
                }

                this.insertDownloadLink(fileItem)
            })

            this.pond.on('initfile', async (fileItem) => {
                if (!isOpenable) {
                    return
                }

                if (isAvatar) {
                    return
                }

                this.insertOpenLink(fileItem)
            })

            // 下面这个没搞懂，主要是 dispatch 
            // this.pond.on('addfilestart', async (file) => {
            //     if (file.status !== FilePond.FileStatus.PROCESSING_QUEUED) {
            //         return
            //     }

            //     this.dispatchFormEvent('form-processing-started', {
            //         message: uploadingMessage,
            //     })
            // })
            // const handleFileProcessing = async () => {
            //     if (
            //         this.pond
            //             .getFiles()
            //             .filter(
            //                 (file) =>
            //                     file.status ===
            //                         FilePond.FileStatus.PROCESSING ||
            //                     file.status ===
            //                         FilePond.FileStatus.PROCESSING_QUEUED,
            //             ).length
            //     ) {
            //         return
            //     }
            //     this.dispatchFormEvent('form-processing-finished')
            // }

            // this.pond.on('processfile', handleFileProcessing)

            // this.pond.on('processfileabort', handleFileProcessing)

            // this.pond.on('processfilerevert', handleFileProcessing)

            // if (panelLayout === 'compact circle') {
            //     // The compact circle layout does not have enough space to render an error message inside the input.
            //     // As such, we need to display the error message outside of the input, using the `error` Alpine.js
            //     // property that is output as a message in the field's view.

            //     this.pond.on('error', (error) => {
            //         // FilePond has a weird English translation for the error message when a file of an unexpected
            //         // type is uploaded, for example: `File of invalid type: Expects  or image/*`. This is a
            //         // hacky workaround to fix the message to be `File of invalid type: Expects image/*`.
            //         this.error = `${error.main}: ${error.sub}`.replace(
            //             'Expects  or',
            //             'Expects',
            //         )
            //     })

            //     this.pond.on('removefile', () => (this.error = null))
            // }
        },

        getUploadedFiles: async function () {
            const uploadedFiles = await getUploadedFilesUsing()

            this.fileKeyIndex = uploadedFiles ?? {}
console.log(this.fileKeyIndex, 'fileKeyIndex');
            this.uploadedFileIndex = Object.entries(this.fileKeyIndex)
                .filter(([key, value]) => value?.url)
                .reduce((obj, [key, value]) => {
                    obj[value.url] = key

                    return obj
                }, {})

console.log(this.uploadedFileIndex, 'uploadedFileIndex');
        },

        getFiles: async function () {
            await this.getUploadedFiles()

            let files = []

            for (const uploadedFile of Object.values(this.fileKeyIndex)) {
                if (!uploadedFile) {
                    continue
                }

                console.log(uploadedFile, 'uploadedFile');

                let fileOptions = !uploadedFile.type ||
                    (isPreviewable && (
                        /^audio/.test(uploadedFile.type) ||
                        /^image/.test(uploadedFile.type) ||
                        /^video/.test(uploadedFile.type)
                    )) ? {} : {
                        file: {
                            name: uploadedFile.name,
                            size: uploadedFile.size,
                            type: uploadedFile.type,
                        },
                    };

                console.log(fileOptions, 'fileOptions');

                files.push({
                    source: uploadedFile.url,
                    options: {
                        type: 'local',
                        ...fileOptions,
                    },
                })
            }

            return shouldAppendFiles ? files : files.reverse()
        },
        insertDownloadLink: function (file) {
            if (file.origin !== FilePond.FileOrigin.LOCAL) {
                return
            }

            const anchor = this.getDownloadLink(file)

            if (!anchor) {
                return
            }

            document
                .getElementById(`filepond--item-${file.id}`)
                .querySelector('.filepond--file-info-main')
                .prepend(anchor)
        },

        insertOpenLink: function (file) {
            if (file.origin !== FilePond.FileOrigin.LOCAL) {
                return
            }

            const anchor = this.getOpenLink(file)

            if (!anchor) {
                return
            }

            document
                .getElementById(`filepond--item-${file.id}`)
                .querySelector('.filepond--file-info-main')
                .prepend(anchor)
        },
        getDownloadLink: function (file) {
            let fileSource = file.source

            if (!fileSource) {
                return
            }

            const anchor = document.createElement('a')
            anchor.className = 'filepond--download-icon'
            anchor.href = fileSource
            anchor.download = file.file.name

            return anchor
        },
        getOpenLink: function (file) {
            let fileSource = file.source

            if (!fileSource) {
                return
            }

            const anchor = document.createElement('a')
            anchor.className = 'filepond--open-icon'
            anchor.href = fileSource
            anchor.target = '_blank'

            return anchor
        },


        dispatchFormEvent: function (name, detail = {}) {
            this.$el.closest('form')?.dispatchEvent(
                new CustomEvent(name, {
                    composed: true,
                    cancelable: true,
                    detail,
                }),
            )
        },
    }
}

import ar from 'filepond/locale/ar-ar'
import ca from 'filepond/locale/ca-ca'
import ckb from 'filepond/locale/ku-ckb'
import cs from 'filepond/locale/cs-cz'
import da from 'filepond/locale/da-dk'
import de from 'filepond/locale/de-de'
import en from 'filepond/locale/en-en'
import es from 'filepond/locale/es-es'
import fa from 'filepond/locale/fa_ir'
import fi from 'filepond/locale/fi-fi'
import fr from 'filepond/locale/fr-fr'
import hu from 'filepond/locale/hu-hu'
import id from 'filepond/locale/id-id'
import it from 'filepond/locale/it-it'
import km from 'filepond/locale/km-km'
import nl from 'filepond/locale/nl-nl'
import no from 'filepond/locale/no_nb'
import pl from 'filepond/locale/pl-pl'
import pt_BR from 'filepond/locale/pt-br'
import pt_PT from 'filepond/locale/pt-br'
import ro from 'filepond/locale/ro-ro'
import ru from 'filepond/locale/ru-ru'
import sv from 'filepond/locale/sv_se'
import tr from 'filepond/locale/tr-tr'
import uk from 'filepond/locale/uk-ua'
import vi from 'filepond/locale/vi-vi'
import zh_CN from 'filepond/locale/zh-cn'
import zh_TW from 'filepond/locale/zh-tw'

const locales = {
    ar,
    ca,
    ckb,
    cs,
    da,
    de,
    en,
    es,
    fa,
    fi,
    fr,
    hu,
    id,
    it,
    km,
    nl,
    no,
    pl,
    pt_BR,
    pt_PT,
    ro,
    ru,
    sv,
    tr,
    uk,
    vi,
    zh_CN,
    zh_TW,
}
