(() => {
    "use strict";
    const modules_flsModules = typeof window !== 'undefined' && window.modules_flsModules ? window.modules_flsModules : {};
    if (typeof window !== 'undefined') {
        window.modules_flsModules = modules_flsModules;
    }
    function getHash() {
        if (location.hash) return location.hash.replace("#", "");
    }
    function setHash(hash) {
        hash = hash ? `#${hash}` : window.location.href.split("#")[0];
        history.pushState("", "", hash);
    }
    let _slideUp = (target, duration = 500, showmore = 0) => {
        if (!target.classList.contains("_slide")) {
            target.classList.add("_slide");
            target.style.transitionProperty = "height, margin, padding";
            target.style.transitionDuration = duration + "ms";
            target.style.height = `${target.offsetHeight}px`;
            target.offsetHeight;
            target.style.overflow = "hidden";
            target.style.height = showmore ? `${showmore}px` : `0px`;
            target.style.paddingTop = 0;
            target.style.paddingBottom = 0;
            target.style.marginTop = 0;
            target.style.marginBottom = 0;
            window.setTimeout(() => {
                target.hidden = !showmore ? true : false;
                !showmore ? target.style.removeProperty("height") : null;
                target.style.removeProperty("padding-top");
                target.style.removeProperty("padding-bottom");
                target.style.removeProperty("margin-top");
                target.style.removeProperty("margin-bottom");
                !showmore ? target.style.removeProperty("overflow") : null;
                target.style.removeProperty("transition-duration");
                target.style.removeProperty("transition-property");
                target.classList.remove("_slide");
                document.dispatchEvent(new CustomEvent("slideUpDone", {
                    detail: {
                        target
                    }
                }));
            }, duration);
        }
    };
    let _slideDown = (target, duration = 500, showmore = 0) => {
        if (!target.classList.contains("_slide")) {
            target.classList.add("_slide");
            target.hidden = target.hidden ? false : null;
            showmore ? target.style.removeProperty("height") : null;
            let height = target.offsetHeight;
            target.style.overflow = "hidden";
            target.style.height = showmore ? `${showmore}px` : `0px`;
            target.style.paddingTop = 0;
            target.style.paddingBottom = 0;
            target.style.marginTop = 0;
            target.style.marginBottom = 0;
            target.offsetHeight;
            target.style.transitionProperty = "height, margin, padding";
            target.style.transitionDuration = duration + "ms";
            target.style.height = height + "px";
            target.style.removeProperty("padding-top");
            target.style.removeProperty("padding-bottom");
            target.style.removeProperty("margin-top");
            target.style.removeProperty("margin-bottom");
            window.setTimeout(() => {
                target.style.removeProperty("height");
                target.style.removeProperty("overflow");
                target.style.removeProperty("transition-duration");
                target.style.removeProperty("transition-property");
                target.classList.remove("_slide");
                document.dispatchEvent(new CustomEvent("slideDownDone", {
                    detail: {
                        target
                    }
                }));
            }, duration);
        }
    };
    let _slideToggle = (target, duration = 500) => {
        if (target.hidden) return _slideDown(target, duration); else return _slideUp(target, duration);
    };
    let bodyLockStatus = true;
    let bodyUnlock = (delay = 500) => {
        if (bodyLockStatus) {
            const lockPaddingElements = document.querySelectorAll("[data-lp]");
            setTimeout(() => {
                lockPaddingElements.forEach(lockPaddingElement => {
                    lockPaddingElement.style.paddingRight = "";
                });
                document.body.style.paddingRight = "";
                document.documentElement.classList.remove("lock");
            }, delay);
            bodyLockStatus = false;
            setTimeout(function() {
                bodyLockStatus = true;
            }, delay);
        }
    };
    let bodyLock = (delay = 500) => {
        if (bodyLockStatus) {
            const lockPaddingElements = document.querySelectorAll("[data-lp]");
            const lockPaddingValue = window.innerWidth - document.body.offsetWidth + "px";
            lockPaddingElements.forEach(lockPaddingElement => {
                lockPaddingElement.style.paddingRight = lockPaddingValue;
            });
            document.body.style.paddingRight = lockPaddingValue;
            document.documentElement.classList.add("lock");
            bodyLockStatus = false;
            setTimeout(function() {
                bodyLockStatus = true;
            }, delay);
        }
    };
    function tabs() {
        const tabs = document.querySelectorAll("[data-tabs]");
        let tabsActiveHash = [];
        if (tabs.length > 0) {
            const hash = getHash();
            if (hash && hash.startsWith("tab-")) tabsActiveHash = hash.replace("tab-", "").split("-");
            tabs.forEach((tabsBlock, index) => {
                tabsBlock.classList.add("_tab-init");
                tabsBlock.setAttribute("data-tabs-index", index);
                tabsBlock.addEventListener("click", setTabsAction);
                initTabs(tabsBlock);
            });
            let mdQueriesArray = dataMediaQueries(tabs, "tabs");
            if (mdQueriesArray && mdQueriesArray.length) mdQueriesArray.forEach(mdQueriesItem => {
                mdQueriesItem.matchMedia.addEventListener("change", function() {
                    setTitlePosition(mdQueriesItem.itemsArray, mdQueriesItem.matchMedia);
                });
                setTitlePosition(mdQueriesItem.itemsArray, mdQueriesItem.matchMedia);
            });
        }
        function setTitlePosition(tabsMediaArray, matchMedia) {
            tabsMediaArray.forEach(tabsMediaItem => {
                tabsMediaItem = tabsMediaItem.item;
                let tabsTitles = tabsMediaItem.querySelector("[data-tabs-titles]");
                let tabsTitleItems = tabsMediaItem.querySelectorAll("[data-tabs-title]");
                let tabsContent = tabsMediaItem.querySelector("[data-tabs-body]");
                let tabsContentItems = tabsMediaItem.querySelectorAll("[data-tabs-item]");
                tabsTitleItems = Array.from(tabsTitleItems).filter(item => item.closest("[data-tabs]") === tabsMediaItem);
                tabsContentItems = Array.from(tabsContentItems).filter(item => item.closest("[data-tabs]") === tabsMediaItem);
                tabsContentItems.forEach((tabsContentItem, index) => {
                    if (matchMedia.matches) {
                        tabsContent.append(tabsTitleItems[index]);
                        tabsContent.append(tabsContentItem);
                        tabsMediaItem.classList.add("_tab-spoller");
                    } else {
                        tabsTitles.append(tabsTitleItems[index]);
                        tabsMediaItem.classList.remove("_tab-spoller");
                    }
                });
            });
        }
        function initTabs(tabsBlock) {
            let tabsTitles = tabsBlock.querySelectorAll("[data-tabs-titles]>*");
            let tabsContent = tabsBlock.querySelectorAll("[data-tabs-body]>*");
            const tabsBlockIndex = tabsBlock.dataset.tabsIndex;
            const tabsActiveHashBlock = tabsActiveHash[0] == tabsBlockIndex;
            if (tabsActiveHashBlock) {
                const tabsActiveTitle = tabsBlock.querySelector("[data-tabs-titles]>._tab-active");
                tabsActiveTitle ? tabsActiveTitle.classList.remove("_tab-active") : null;
            }
            if (tabsContent.length) tabsContent.forEach((tabsContentItem, index) => {
                tabsTitles[index].setAttribute("data-tabs-title", "");
                tabsContentItem.setAttribute("data-tabs-item", "");
                if (tabsActiveHashBlock && index == tabsActiveHash[1]) tabsTitles[index].classList.add("_tab-active");
                tabsContentItem.hidden = !tabsTitles[index].classList.contains("_tab-active");
            });
        }
        function setTabsStatus(tabsBlock) {
            let tabsTitles = tabsBlock.querySelectorAll("[data-tabs-title]");
            let tabsContent = tabsBlock.querySelectorAll("[data-tabs-item]");
            const tabsBlockIndex = tabsBlock.dataset.tabsIndex;
            function isTabsAnamate(tabsBlock) {
                if (tabsBlock.hasAttribute("data-tabs-animate")) return tabsBlock.dataset.tabsAnimate > 0 ? Number(tabsBlock.dataset.tabsAnimate) : 500;
            }
            const tabsBlockAnimate = isTabsAnamate(tabsBlock);
            if (tabsContent.length > 0) {
                const isHash = tabsBlock.hasAttribute("data-tabs-hash");
                tabsContent = Array.from(tabsContent).filter(item => item.closest("[data-tabs]") === tabsBlock);
                tabsTitles = Array.from(tabsTitles).filter(item => item.closest("[data-tabs]") === tabsBlock);
                tabsContent.forEach((tabsContentItem, index) => {
                    if (tabsTitles[index].classList.contains("_tab-active")) {
                        if (tabsBlockAnimate) _slideDown(tabsContentItem, tabsBlockAnimate); else tabsContentItem.hidden = false;
                        if (isHash && !tabsContentItem.closest(".popup")) setHash(`tab-${tabsBlockIndex}-${index}`);
                    } else if (tabsBlockAnimate) _slideUp(tabsContentItem, tabsBlockAnimate); else tabsContentItem.hidden = true;
                });
            }
        }
        function setTabsAction(e) {
            const el = e.target;
            if (el.closest("[data-tabs-title]")) {
                const tabTitle = el.closest("[data-tabs-title]");
                const tabsBlock = tabTitle.closest("[data-tabs]");
                if (!tabTitle.classList.contains("_tab-active") && !tabsBlock.querySelector("._slide")) {
                    let tabActiveTitle = tabsBlock.querySelectorAll("[data-tabs-title]._tab-active");
                    tabActiveTitle.length ? tabActiveTitle = Array.from(tabActiveTitle).filter(item => item.closest("[data-tabs]") === tabsBlock) : null;
                    tabActiveTitle.length ? tabActiveTitle[0].classList.remove("_tab-active") : null;
                    tabTitle.classList.add("_tab-active");
                    setTabsStatus(tabsBlock);
                }
                e.preventDefault();
            }
        }
    }
    function uniqArray(array) {
        return array.filter(function(item, index, self) {
            return self.indexOf(item) === index;
        });
    }
    function dataMediaQueries(array, dataSetValue) {
        const media = Array.from(array).filter(function(item, index, self) {
            if (item.dataset[dataSetValue]) return item.dataset[dataSetValue].split(",")[0];
        });
        if (media.length) {
            const breakpointsArray = [];
            media.forEach(item => {
                const params = item.dataset[dataSetValue];
                const breakpoint = {};
                const paramsArray = params.split(",");
                breakpoint.value = paramsArray[0];
                breakpoint.type = paramsArray[1] ? paramsArray[1].trim() : "max";
                breakpoint.item = item;
                breakpointsArray.push(breakpoint);
            });
            let mdQueries = breakpointsArray.map(function(item) {
                return "(" + item.type + "-width: " + item.value + "px)," + item.value + "," + item.type;
            });
            mdQueries = uniqArray(mdQueries);
            const mdQueriesArray = [];
            if (mdQueries.length) {
                mdQueries.forEach(breakpoint => {
                    const paramsArray = breakpoint.split(",");
                    const mediaBreakpoint = paramsArray[1];
                    const mediaType = paramsArray[2];
                    const matchMedia = window.matchMedia(paramsArray[0]);
                    const itemsArray = breakpointsArray.filter(function(item) {
                        if (item.value === mediaBreakpoint && item.type === mediaType) return true;
                    });
                    mdQueriesArray.push({
                        itemsArray,
                        matchMedia
                    });
                });
                return mdQueriesArray;
            }
        }
    }
    class Popup {
        constructor(options) {
            let config = {
                init: true,
                attributeOpenButton: "data-popup",
                attributeCloseButton: "data-close",
                fixElementSelector: "[data-lp]",
                youtubeAttribute: "data-popup-youtube",
                youtubePlaceAttribute: "data-popup-youtube-place",
                setAutoplayYoutube: true,
                classes: {
                    popup: "popup",
                    popupContent: "popup__content",
                    popupActive: "popup_show",
                    bodyActive: "popup-show"
                },
                focusCatch: true,
                closeEsc: true,
                bodyLock: true,
                hashSettings: {
                    location: true,
                    goHash: true
                },
                on: {
                    beforeOpen: function() {},
                    afterOpen: function() {},
                    beforeClose: function() {},
                    afterClose: function() {}
                }
            };
            this.youTubeCode;
            this.isOpen = false;
            this.targetOpen = {
                selector: false,
                element: false
            };
            this.previousOpen = {
                selector: false,
                element: false
            };
            this.lastClosed = {
                selector: false,
                element: false
            };
            this._dataValue = false;
            this.hash = false;
            this._reopen = false;
            this._selectorOpen = false;
            this.lastFocusEl = false;
            this._focusEl = [ "a[href]", 'input:not([disabled]):not([type="hidden"]):not([aria-hidden])', "button:not([disabled]):not([aria-hidden])", "select:not([disabled]):not([aria-hidden])", "textarea:not([disabled]):not([aria-hidden])", "area[href]", "iframe", "object", "embed", "[contenteditable]", '[tabindex]:not([tabindex^="-"])' ];
            this.options = {
                ...config,
                ...options,
                classes: {
                    ...config.classes,
                    ...options?.classes
                },
                hashSettings: {
                    ...config.hashSettings,
                    ...options?.hashSettings
                },
                on: {
                    ...config.on,
                    ...options?.on
                }
            };
            this.bodyLock = false;
            this.options.init ? this.initPopups() : null;
        }
        initPopups() {
            this.eventsPopup();
        }
        eventsPopup() {
            document.addEventListener("click", function(e) {
                const buttonOpen = e.target.closest(`[${this.options.attributeOpenButton}]`);
                if (buttonOpen) {
                    e.preventDefault();
                    this._dataValue = buttonOpen.getAttribute(this.options.attributeOpenButton) ? buttonOpen.getAttribute(this.options.attributeOpenButton) : "error";
                    this.youTubeCode = buttonOpen.getAttribute(this.options.youtubeAttribute) ? buttonOpen.getAttribute(this.options.youtubeAttribute) : null;
                    if (this._dataValue !== "error") {
                        if (!this.isOpen) this.lastFocusEl = buttonOpen;
                        this.targetOpen.selector = `${this._dataValue}`;
                        this._selectorOpen = true;
                        this.open();
                        return;
                    }
                    return;
                }
                const buttonClose = e.target.closest(`[${this.options.attributeCloseButton}]`);
                if (buttonClose || !e.target.closest(`.${this.options.classes.popupContent}`) && this.isOpen) {
                    e.preventDefault();
                    this.close();
                    return;
                }
            }.bind(this));
            document.addEventListener("keydown", function(e) {
                if (this.options.closeEsc && e.which == 27 && e.code === "Escape" && this.isOpen) {
                    e.preventDefault();
                    this.close();
                    return;
                }
                if (this.options.focusCatch && e.which == 9 && this.isOpen) {
                    this._focusCatch(e);
                    return;
                }
            }.bind(this));
            if (this.options.hashSettings.goHash) {
                window.addEventListener("hashchange", function() {
                    if (window.location.hash) this._openToHash(); else this.close(this.targetOpen.selector);
                }.bind(this));
                window.addEventListener("load", function() {
                    if (window.location.hash) this._openToHash();
                }.bind(this));
            }
        }
        open(selectorValue) {
            if (bodyLockStatus) {
                this.bodyLock = document.documentElement.classList.contains("lock") && !this.isOpen ? true : false;
                if (selectorValue && typeof selectorValue === "string" && selectorValue.trim() !== "") {
                    this.targetOpen.selector = selectorValue;
                    this._selectorOpen = true;
                }
                if (this.isOpen) {
                    this._reopen = true;
                    this.close();
                }
                if (!this._selectorOpen) this.targetOpen.selector = this.lastClosed.selector;
                if (!this._reopen) this.previousActiveElement = document.activeElement;
                this.targetOpen.element = document.querySelector(this.targetOpen.selector);
                if (this.targetOpen.element) {
                    if (this.youTubeCode) {
                        const codeVideo = this.youTubeCode;
                        const urlVideo = `https://www.youtube.com/embed/${codeVideo}?rel=0&showinfo=0&autoplay=1`;
                        const iframe = document.createElement("iframe");
                        iframe.setAttribute("allowfullscreen", "");
                        const autoplay = this.options.setAutoplayYoutube ? "autoplay;" : "";
                        iframe.setAttribute("allow", `${autoplay}; encrypted-media`);
                        iframe.setAttribute("src", urlVideo);
                        if (!this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`)) {
                            this.targetOpen.element.querySelector(".popup__text").setAttribute(`${this.options.youtubePlaceAttribute}`, "");
                        }
                        this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`).appendChild(iframe);
                    }
                    if (this.options.hashSettings.location) {
                        this._getHash();
                        this._setHash();
                    }
                    this.options.on.beforeOpen(this);
                    document.dispatchEvent(new CustomEvent("beforePopupOpen", {
                        detail: {
                            popup: this
                        }
                    }));
                    this.targetOpen.element.classList.add(this.options.classes.popupActive);
                    document.documentElement.classList.add(this.options.classes.bodyActive);
                    if (!this._reopen) !this.bodyLock ? bodyLock() : null; else this._reopen = false;
                    this.targetOpen.element.setAttribute("aria-hidden", "false");
                    this.previousOpen.selector = this.targetOpen.selector;
                    this.previousOpen.element = this.targetOpen.element;
                    this._selectorOpen = false;
                    this.isOpen = true;
                    setTimeout(() => {
                        this._focusTrap();
                    }, 50);
                    this.options.on.afterOpen(this);
                    document.dispatchEvent(new CustomEvent("afterPopupOpen", {
                        detail: {
                            popup: this
                        }
                    }));
                }
            }
        }
        close(selectorValue) {
            if (selectorValue && typeof selectorValue === "string" && selectorValue.trim() !== "") this.previousOpen.selector = selectorValue;
            if (!this.isOpen || !bodyLockStatus) return;
            this.options.on.beforeClose(this);
            document.dispatchEvent(new CustomEvent("beforePopupClose", {
                detail: {
                    popup: this
                }
            }));
            if (this.youTubeCode) if (this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`)) this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`).innerHTML = "";
            this.previousOpen.element.classList.remove(this.options.classes.popupActive);
            this.previousOpen.element.setAttribute("aria-hidden", "true");
            if (!this._reopen) {
                document.documentElement.classList.remove(this.options.classes.bodyActive);
                !this.bodyLock ? bodyUnlock() : null;
                this.isOpen = false;
            }
            this._removeHash();
            if (this._selectorOpen) {
                this.lastClosed.selector = this.previousOpen.selector;
                this.lastClosed.element = this.previousOpen.element;
            }
            this.options.on.afterClose(this);
            document.dispatchEvent(new CustomEvent("afterPopupClose", {
                detail: {
                    popup: this
                }
            }));
            setTimeout(() => {
                this._focusTrap();
            }, 50);
        }
        _getHash() {
            if (this.options.hashSettings.location) this.hash = this.targetOpen.selector.includes("#") ? this.targetOpen.selector : this.targetOpen.selector.replace(".", "#");
        }
        _openToHash() {
            let classInHash = document.querySelector(`.${window.location.hash.replace("#", "")}`) ? `.${window.location.hash.replace("#", "")}` : document.querySelector(`${window.location.hash}`) ? `${window.location.hash}` : null;
            if (!classInHash) return;
            const buttons = document.querySelector(`[${this.options.attributeOpenButton} = "${classInHash}"]`) ? document.querySelector(`[${this.options.attributeOpenButton} = "${classInHash}"]`) : document.querySelector(`[${this.options.attributeOpenButton} = "${classInHash.replace(".", "#")}"]`);
            if (!buttons) return;
            this.youTubeCode = buttons.getAttribute(this.options.youtubeAttribute) ? buttons.getAttribute(this.options.youtubeAttribute) : null;
            if (buttons && classInHash) this.open(classInHash);
        }
        _setHash() {
            history.pushState("", "", this.hash);
        }
        _removeHash() {
            history.pushState("", "", window.location.href.split("#")[0]);
        }
        _focusCatch(e) {
            const focusable = this.targetOpen.element.querySelectorAll(this._focusEl);
            const focusArray = Array.prototype.slice.call(focusable);
            const focusedIndex = focusArray.indexOf(document.activeElement);
            if (e.shiftKey && focusedIndex === 0) {
                focusArray[focusArray.length - 1].focus();
                e.preventDefault();
            }
            if (!e.shiftKey && focusedIndex === focusArray.length - 1) {
                focusArray[0].focus();
                e.preventDefault();
            }
        }
        _focusTrap() {
            const focusable = this.previousOpen.element.querySelectorAll(this._focusEl);
            if (!this.isOpen && this.lastFocusEl) this.lastFocusEl.focus(); else focusable[0].focus();
        }
    }
    modules_flsModules.popup = new Popup({});

    const handleExternalPopupOpen = event => {
        if (!event) return;
        const detail = event.detail;
        const selector = typeof detail === 'string' ? detail : detail?.selector;
        if (selector && modules_flsModules?.popup) {
            modules_flsModules.popup.open(selector);
        }
    };

    document.addEventListener('openPopup', handleExternalPopupOpen);
    if (typeof window !== 'undefined') {
        window.openPopup = selector => {
            handleExternalPopupOpen({ detail: selector });
        };
    }
    let formValidate = {
        getErrors(form) {
            let error = 0;
            let formRequiredItems = form.querySelectorAll("*[data-required]");
            if (formRequiredItems.length) formRequiredItems.forEach(formRequiredItem => {
                if ((formRequiredItem.offsetParent !== null || formRequiredItem.tagName === "SELECT") && !formRequiredItem.disabled) error += this.validateInput(formRequiredItem);
            });
            return error;
        },
        validateInput(formRequiredItem) {
            let error = 0;
            if (formRequiredItem.dataset.required === "email") {
                formRequiredItem.value = formRequiredItem.value.replace(" ", "");
                if (this.emailTest(formRequiredItem)) {
                    this.addError(formRequiredItem);
                    error++;
                } else this.removeError(formRequiredItem);
            } else if (formRequiredItem.type === "checkbox" && !formRequiredItem.checked) {
                this.addError(formRequiredItem);
                error++;
            } else if (!formRequiredItem.value.trim()) {
                this.addError(formRequiredItem);
                error++;
            } else this.removeError(formRequiredItem);
            return error;
        },
        addError(formRequiredItem) {
            formRequiredItem.classList.add("_form-error");
            formRequiredItem.parentElement.classList.add("_form-error");
            let inputError = formRequiredItem.parentElement.querySelector(".form__error");
            if (inputError) formRequiredItem.parentElement.removeChild(inputError);
            if (formRequiredItem.dataset.error) formRequiredItem.parentElement.insertAdjacentHTML("beforeend", `<div class="form__error">${formRequiredItem.dataset.error}</div>`);
        },
        removeError(formRequiredItem) {
            formRequiredItem.classList.remove("_form-error");
            formRequiredItem.parentElement.classList.remove("_form-error");
            if (formRequiredItem.parentElement.querySelector(".form__error")) formRequiredItem.parentElement.removeChild(formRequiredItem.parentElement.querySelector(".form__error"));
        },
        formClean(form) {
            form.reset();
            setTimeout(() => {
                let inputs = form.querySelectorAll("input,textarea");
                for (let index = 0; index < inputs.length; index++) {
                    const el = inputs[index];
                    el.parentElement.classList.remove("_form-focus");
                    el.classList.remove("_form-focus");
                    formValidate.removeError(el);
                }
                let checkboxes = form.querySelectorAll(".checkbox__input");
                if (checkboxes.length > 0) for (let index = 0; index < checkboxes.length; index++) {
                    const checkbox = checkboxes[index];
                    checkbox.checked = false;
                }
                if (modules_flsModules.select) {
                    let selects = form.querySelectorAll("div.select");
                    if (selects.length) for (let index = 0; index < selects.length; index++) {
                        const select = selects[index].querySelector("select");
                        modules_flsModules.select.selectBuild(select);
                    }
                }
            }, 0);
        },
        emailTest(formRequiredItem) {
            return !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,8})+$/.test(formRequiredItem.value);
        }
    };
    class SelectConstructor {
        constructor(props, data = null) {
            let defaultConfig = {
                init: true,
                speed: 150
            };
            this.config = Object.assign(defaultConfig, props);
            this.selectClasses = {
                classSelect: "select",
                classSelectBody: "select__body",
                classSelectTitle: "select__title",
                classSelectValue: "select__value",
                classSelectLabel: "select__label",
                classSelectInput: "select__input",
                classSelectText: "select__text",
                classSelectLink: "select__link",
                classSelectOptions: "select__options",
                classSelectOptionsScroll: "select__scroll",
                classSelectOption: "select__option",
                classSelectContent: "select__content",
                classSelectRow: "select__row",
                classSelectData: "select__asset",
                classSelectDisabled: "_select-disabled",
                classSelectTag: "_select-tag",
                classSelectOpen: "_select-open",
                classSelectActive: "_select-active",
                classSelectFocus: "_select-focus",
                classSelectMultiple: "_select-multiple",
                classSelectCheckBox: "_select-checkbox",
                classSelectOptionSelected: "_select-selected",
                classSelectPseudoLabel: "_select-pseudo-label"
            };
            this._this = this;
            if (this.config.init) {
                const selectItems = data ? document.querySelectorAll(data) : document.querySelectorAll("select");
                if (selectItems.length) this.selectsInit(selectItems);
            }
        }
        getSelectClass(className) {
            return `.${className}`;
        }
        getSelectElement(selectItem, className) {
            return {
                originalSelect: selectItem.querySelector("select"),
                selectElement: selectItem.querySelector(this.getSelectClass(className))
            };
        }
        selectsInit(selectItems) {
            selectItems.forEach((originalSelect, index) => {
                this.selectInit(originalSelect, index + 1);
            });
            document.addEventListener("click", function(e) {
                this.selectsActions(e);
            }.bind(this));
            document.addEventListener("keydown", function(e) {
                this.selectsActions(e);
            }.bind(this));
            document.addEventListener("focusin", function(e) {
                this.selectsActions(e);
            }.bind(this));
            document.addEventListener("focusout", function(e) {
                this.selectsActions(e);
            }.bind(this));
        }
        selectInit(originalSelect, index) {
            const _this = this;
            let selectItem = document.createElement("div");
            selectItem.classList.add(this.selectClasses.classSelect);
            originalSelect.parentNode.insertBefore(selectItem, originalSelect);
            selectItem.appendChild(originalSelect);
            originalSelect.hidden = true;
            index ? originalSelect.dataset.id = index : null;
            if (this.getSelectPlaceholder(originalSelect)) {
                originalSelect.dataset.placeholder = this.getSelectPlaceholder(originalSelect).value;
                if (this.getSelectPlaceholder(originalSelect).label.show) {
                    const selectItemTitle = this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement;
                    selectItemTitle.insertAdjacentHTML("afterbegin", `<span class="${this.selectClasses.classSelectLabel}">${this.getSelectPlaceholder(originalSelect).label.text ? this.getSelectPlaceholder(originalSelect).label.text : this.getSelectPlaceholder(originalSelect).value}</span>`);
                }
            }
            selectItem.insertAdjacentHTML("beforeend", `<div class="${this.selectClasses.classSelectBody}"><div hidden class="${this.selectClasses.classSelectOptions}"></div></div>`);
            this.selectBuild(originalSelect);
            originalSelect.dataset.speed = originalSelect.dataset.speed ? originalSelect.dataset.speed : this.config.speed;
            this.config.speed = +originalSelect.dataset.speed;
            originalSelect.addEventListener("change", function(e) {
                _this.selectChange(e);
            });
        }
        selectBuild(originalSelect) {
            const selectItem = originalSelect.parentElement;
            selectItem.dataset.id = originalSelect.dataset.id;
            originalSelect.dataset.classModif ? selectItem.classList.add(`select_${originalSelect.dataset.classModif}`) : null;
            originalSelect.multiple ? selectItem.classList.add(this.selectClasses.classSelectMultiple) : selectItem.classList.remove(this.selectClasses.classSelectMultiple);
            originalSelect.hasAttribute("data-checkbox") && originalSelect.multiple ? selectItem.classList.add(this.selectClasses.classSelectCheckBox) : selectItem.classList.remove(this.selectClasses.classSelectCheckBox);
            this.setSelectTitleValue(selectItem, originalSelect);
            this.setOptions(selectItem, originalSelect);
            originalSelect.hasAttribute("data-search") ? this.searchActions(selectItem) : null;
            originalSelect.hasAttribute("data-open") ? this.selectAction(selectItem) : null;
            this.selectDisabled(selectItem, originalSelect);
        }
        selectsActions(e) {
            const targetElement = e.target;
            const targetType = e.type;
            if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelect)) || targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag))) {
                const selectItem = targetElement.closest(".select") ? targetElement.closest(".select") : document.querySelector(`.${this.selectClasses.classSelect}[data-id="${targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag)).dataset.selectId}"]`);
                const originalSelect = this.getSelectElement(selectItem).originalSelect;
                if (targetType === "click") {
                    if (!originalSelect.disabled) if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag))) {
                        const targetTag = targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag));
                        const optionItem = document.querySelector(`.${this.selectClasses.classSelect}[data-id="${targetTag.dataset.selectId}"] .select__option[data-value="${targetTag.dataset.value}"]`);
                        this.optionAction(selectItem, originalSelect, optionItem);
                    } else if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTitle))) this.selectAction(selectItem); else if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectOption))) {
                        const optionItem = targetElement.closest(this.getSelectClass(this.selectClasses.classSelectOption));
                        this.optionAction(selectItem, originalSelect, optionItem);
                    }
                } else if (targetType === "focusin" || targetType === "focusout") {
                    if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelect))) targetType === "focusin" ? selectItem.classList.add(this.selectClasses.classSelectFocus) : selectItem.classList.remove(this.selectClasses.classSelectFocus);
                } else if (targetType === "keydown" && e.code === "Escape") this.selectsСlose();
            } else this.selectsСlose();
        }
        selectsСlose(selectOneGroup) {
            const selectsGroup = selectOneGroup ? selectOneGroup : document;
            const selectActiveItems = selectsGroup.querySelectorAll(`${this.getSelectClass(this.selectClasses.classSelect)}${this.getSelectClass(this.selectClasses.classSelectOpen)}`);
            if (selectActiveItems.length) selectActiveItems.forEach(selectActiveItem => {
                this.selectСlose(selectActiveItem);
            });
        }
        selectСlose(selectItem) {
            const originalSelect = this.getSelectElement(selectItem).originalSelect;
            const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
            if (!selectOptions.classList.contains("_slide")) {
                selectItem.classList.remove(this.selectClasses.classSelectOpen);
                _slideUp(selectOptions, originalSelect.dataset.speed);
                setTimeout(() => {
                    selectItem.style.zIndex = "";
                }, originalSelect.dataset.speed);
            }
        }
        selectAction(selectItem) {
            const originalSelect = this.getSelectElement(selectItem).originalSelect;
            const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
            const selectOpenzIndex = originalSelect.dataset.zIndex ? originalSelect.dataset.zIndex : 3;
            this.setOptionsPosition(selectItem);
            if (originalSelect.closest("[data-one-select]")) {
                const selectOneGroup = originalSelect.closest("[data-one-select]");
                this.selectsСlose(selectOneGroup);
            }
            setTimeout(() => {
                if (!selectOptions.classList.contains("_slide")) {
                    selectItem.classList.toggle(this.selectClasses.classSelectOpen);
                    _slideToggle(selectOptions, originalSelect.dataset.speed);
                    if (selectItem.classList.contains(this.selectClasses.classSelectOpen)) selectItem.style.zIndex = selectOpenzIndex; else setTimeout(() => {
                        selectItem.style.zIndex = "";
                    }, originalSelect.dataset.speed);
                }
            }, 0);
        }
        setSelectTitleValue(selectItem, originalSelect) {
            const selectItemBody = this.getSelectElement(selectItem, this.selectClasses.classSelectBody).selectElement;
            const selectItemTitle = this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement;
            if (selectItemTitle) selectItemTitle.remove();
            selectItemBody.insertAdjacentHTML("afterbegin", this.getSelectTitleValue(selectItem, originalSelect));
            originalSelect.hasAttribute("data-search") ? this.searchActions(selectItem) : null;
        }
        getSelectTitleValue(selectItem, originalSelect) {
            let selectTitleValue = this.getSelectedOptionsData(originalSelect, 2).html;
            if (originalSelect.multiple && originalSelect.hasAttribute("data-tags")) {
                selectTitleValue = this.getSelectedOptionsData(originalSelect).elements.map(option => `<span role="button" data-select-id="${selectItem.dataset.id}" data-value="${option.value}" class="_select-tag">${this.getSelectElementContent(option)}</span>`).join("");
                if (originalSelect.dataset.tags && document.querySelector(originalSelect.dataset.tags)) {
                    document.querySelector(originalSelect.dataset.tags).innerHTML = selectTitleValue;
                    if (originalSelect.hasAttribute("data-search")) selectTitleValue = false;
                }
            }
            selectTitleValue = selectTitleValue.length ? selectTitleValue : originalSelect.dataset.placeholder ? originalSelect.dataset.placeholder : "";
            let pseudoAttribute = "";
            let pseudoAttributeClass = "";
            if (originalSelect.hasAttribute("data-pseudo-label")) {
                pseudoAttribute = originalSelect.dataset.pseudoLabel ? ` data-pseudo-label="${originalSelect.dataset.pseudoLabel}"` : ` data-pseudo-label="Заповніть атрибут"`;
                pseudoAttributeClass = ` ${this.selectClasses.classSelectPseudoLabel}`;
            }
            this.getSelectedOptionsData(originalSelect).values.length ? selectItem.classList.add(this.selectClasses.classSelectActive) : selectItem.classList.remove(this.selectClasses.classSelectActive);
            if (originalSelect.hasAttribute("data-search")) return `<div class="${this.selectClasses.classSelectTitle}"><span${pseudoAttribute} class="${this.selectClasses.classSelectValue}"><input autocomplete="off" type="text" placeholder="${selectTitleValue}" data-placeholder="${selectTitleValue}" class="${this.selectClasses.classSelectInput}"></span></div>`; else {
                const customClass = this.getSelectedOptionsData(originalSelect).elements.length && this.getSelectedOptionsData(originalSelect).elements[0].dataset.class ? ` ${this.getSelectedOptionsData(originalSelect).elements[0].dataset.class}` : "";
                return `<button type="button" class="${this.selectClasses.classSelectTitle}"><span${pseudoAttribute} class="${this.selectClasses.classSelectValue}${pseudoAttributeClass}"><span class="${this.selectClasses.classSelectContent}${customClass}">${selectTitleValue}</span></span></button>`;
            }
        }
        getSelectElementContent(selectOption) {
            const selectOptionData = selectOption.dataset.asset ? `${selectOption.dataset.asset}` : "";
            const selectOptionDataHTML = selectOptionData.indexOf("img") >= 0 ? `<img src="${selectOptionData}" alt="">` : selectOptionData;
            let selectOptionContentHTML = ``;
            selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectRow}">` : "";
            selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectData}">` : "";
            selectOptionContentHTML += selectOptionData ? selectOptionDataHTML : "";
            selectOptionContentHTML += selectOptionData ? `</span>` : "";
            selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectText}">` : "";
            selectOptionContentHTML += selectOption.textContent;
            selectOptionContentHTML += selectOptionData ? `</span>` : "";
            selectOptionContentHTML += selectOptionData ? `</span>` : "";
            return selectOptionContentHTML;
        }
        getSelectPlaceholder(originalSelect) {
            const selectPlaceholder = Array.from(originalSelect.options).find(option => !option.value);
            if (selectPlaceholder) return {
                value: selectPlaceholder.textContent,
                show: selectPlaceholder.hasAttribute("data-show"),
                label: {
                    show: selectPlaceholder.hasAttribute("data-label"),
                    text: selectPlaceholder.dataset.label
                }
            };
        }
        getSelectedOptionsData(originalSelect, type) {
            let selectedOptions = [];
            if (originalSelect.multiple) selectedOptions = Array.from(originalSelect.options).filter(option => option.value).filter(option => option.selected); else selectedOptions.push(originalSelect.options[originalSelect.selectedIndex]);
            return {
                elements: selectedOptions.map(option => option),
                values: selectedOptions.filter(option => option.value).map(option => option.value),
                html: selectedOptions.map(option => this.getSelectElementContent(option))
            };
        }
        getOptions(originalSelect) {
            const selectOptionsScroll = originalSelect.hasAttribute("data-scroll") ? `data-simplebar` : "";
            const customMaxHeightValue = +originalSelect.dataset.scroll ? +originalSelect.dataset.scroll : null;
            let selectOptions = Array.from(originalSelect.options);
            if (selectOptions.length > 0) {
                let selectOptionsHTML = ``;
                if (this.getSelectPlaceholder(originalSelect) && !this.getSelectPlaceholder(originalSelect).show || originalSelect.multiple) selectOptions = selectOptions.filter(option => option.value);
                selectOptionsHTML += `<div ${selectOptionsScroll} ${selectOptionsScroll ? `style="max-height: ${customMaxHeightValue}px"` : ""} class="${this.selectClasses.classSelectOptionsScroll}">`;
                selectOptions.forEach(selectOption => {
                    if (selectOption.hasAttribute("data-group")) selectOptionsHTML += this.getGroup(selectOption, originalSelect); else selectOptionsHTML += this.getOption(selectOption, originalSelect);
                });
                selectOptionsHTML += `</div>`;
                return selectOptionsHTML;
            }
        }
        getGroup(selectOption, originalSelect) {
            const groupName = selectOption.dataset.group;
            const groupOptions = Array.from(originalSelect.options).filter(opt => opt.dataset.parent === groupName);
            let groupHTML = `\n\t\t<div class="select__group">\n\t\t\t<div class="select__group-title" data-group="${groupName}">${selectOption.textContent}</div>\n\t\t\t<div class="select__group-content" hidden>\n\t`;
            groupOptions.forEach(opt => {
                groupHTML += this.getOption(opt, originalSelect);
            });
            groupHTML += `</div></div>`;
            return groupHTML;
        }
        getOption(selectOption, originalSelect) {
            const selectOptionSelected = selectOption.selected && originalSelect.multiple ? ` ${this.selectClasses.classSelectOptionSelected}` : "";
            const selectOptionHide = selectOption.selected && !originalSelect.hasAttribute("data-show-selected") && !originalSelect.multiple ? `hidden` : ``;
            const selectOptionClass = selectOption.dataset.class ? ` ${selectOption.dataset.class}` : "";
            const selectOptionLink = selectOption.dataset.href ? selectOption.dataset.href : false;
            const selectOptionLinkTarget = selectOption.hasAttribute("data-href-blank") ? `target="_blank"` : "";
            let selectOptionHTML = ``;
            selectOptionHTML += selectOptionLink ? `<a ${selectOptionLinkTarget} ${selectOptionHide} href="${selectOptionLink}" data-value="${selectOption.value}" class="${this.selectClasses.classSelectOption}${selectOptionClass}${selectOptionSelected}">` : `<button ${selectOptionHide} class="${this.selectClasses.classSelectOption}${selectOptionClass}${selectOptionSelected}" data-value="${selectOption.value}" type="button">`;
            selectOptionHTML += this.getSelectElementContent(selectOption);
            selectOptionHTML += selectOptionLink ? `</a>` : `</button>`;
            return selectOptionHTML;
        }
        setOptions(selectItem, originalSelect) {
            const selectItemOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
            selectItemOptions.innerHTML = this.getOptions(originalSelect);
        }
        setOptionsPosition(selectItem) {
            const originalSelect = this.getSelectElement(selectItem).originalSelect;
            const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
            const selectItemScroll = this.getSelectElement(selectItem, this.selectClasses.classSelectOptionsScroll).selectElement;
            const customMaxHeightValue = +originalSelect.dataset.scroll ? `${+originalSelect.dataset.scroll}px` : ``;
            const selectOptionsPosMargin = +originalSelect.dataset.optionsMargin ? +originalSelect.dataset.optionsMargin : 10;
            if (!selectItem.classList.contains(this.selectClasses.classSelectOpen)) {
                selectOptions.hidden = false;
                const selectItemScrollHeight = selectItemScroll.offsetHeight ? selectItemScroll.offsetHeight : parseInt(window.getComputedStyle(selectItemScroll).getPropertyValue("max-height"));
                const selectOptionsHeight = selectOptions.offsetHeight > selectItemScrollHeight ? selectOptions.offsetHeight : selectItemScrollHeight + selectOptions.offsetHeight;
                const selectOptionsScrollHeight = selectOptionsHeight - selectItemScrollHeight;
                selectOptions.hidden = true;
                const selectItemHeight = selectItem.offsetHeight;
                const selectItemPos = selectItem.getBoundingClientRect().top;
                const selectItemTotal = selectItemPos + selectOptionsHeight + selectItemHeight + selectOptionsScrollHeight;
                const selectItemResult = window.innerHeight - (selectItemTotal + selectOptionsPosMargin);
                if (selectItemResult < 0) {
                    const newMaxHeightValue = selectOptionsHeight + selectItemResult;
                    if (newMaxHeightValue < 100) {
                        selectItem.classList.add("select--show-top");
                        selectItemScroll.style.maxHeight = selectItemPos < selectOptionsHeight ? `${selectItemPos - (selectOptionsHeight - selectItemPos)}px` : customMaxHeightValue;
                    } else {
                        selectItem.classList.remove("select--show-top");
                        selectItemScroll.style.maxHeight = `${newMaxHeightValue}px`;
                    }
                }
            } else setTimeout(() => {
                selectItem.classList.remove("select--show-top");
                selectItemScroll.style.maxHeight = customMaxHeightValue;
            }, +originalSelect.dataset.speed);
        }
        optionAction(selectItem, originalSelect, optionItem) {
            const selectOptions = selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOptions)}`);
            if (!selectOptions.classList.contains("_slide")) {
                if (originalSelect.multiple) {
                    optionItem.classList.toggle(this.selectClasses.classSelectOptionSelected);
                    const originalSelectSelectedItems = this.getSelectedOptionsData(originalSelect).elements;
                    originalSelectSelectedItems.forEach(originalSelectSelectedItem => {
                        originalSelectSelectedItem.removeAttribute("selected");
                    });
                    const selectSelectedItems = selectItem.querySelectorAll(this.getSelectClass(this.selectClasses.classSelectOptionSelected));
                    selectSelectedItems.forEach(selectSelectedItems => {
                        originalSelect.querySelector(`option[value = "${selectSelectedItems.dataset.value}"]`).setAttribute("selected", "selected");
                    });
                } else {
                    if (!originalSelect.hasAttribute("data-show-selected")) setTimeout(() => {
                        if (selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOption)}[hidden]`)) selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOption)}[hidden]`).hidden = false;
                        optionItem.hidden = true;
                    }, this.config.speed);
                    originalSelect.value = optionItem.hasAttribute("data-value") ? optionItem.dataset.value : optionItem.textContent;
                    this.selectAction(selectItem);
                }
                this.setSelectTitleValue(selectItem, originalSelect);
                this.setSelectChange(originalSelect);
            }
        }
        selectChange(e) {
            const originalSelect = e.target;
            this.selectBuild(originalSelect);
            this.setSelectChange(originalSelect);
        }
        setSelectChange(originalSelect) {
            if (originalSelect.hasAttribute("data-validate")) formValidate.validateInput(originalSelect);
            if (originalSelect.hasAttribute("data-submit") && originalSelect.value) {
                let tempButton = document.createElement("button");
                tempButton.type = "submit";
                originalSelect.closest("form").append(tempButton);
                tempButton.click();
                tempButton.remove();
            }
            const selectItem = originalSelect.parentElement;
            this.selectCallback(selectItem, originalSelect);
        }
        selectDisabled(selectItem, originalSelect) {
            if (originalSelect.disabled) {
                selectItem.classList.add(this.selectClasses.classSelectDisabled);
                this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement.disabled = true;
            } else {
                selectItem.classList.remove(this.selectClasses.classSelectDisabled);
                this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement.disabled = false;
            }
        }
        searchActions(selectItem) {
            this.getSelectElement(selectItem).originalSelect;
            const selectInput = this.getSelectElement(selectItem, this.selectClasses.classSelectInput).selectElement;
            const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
            const selectOptionsItems = selectOptions.querySelectorAll(`.${this.selectClasses.classSelectOption} `);
            const _this = this;
            selectInput.addEventListener("input", function() {
                selectOptionsItems.forEach(selectOptionsItem => {
                    if (selectOptionsItem.textContent.toUpperCase().includes(selectInput.value.toUpperCase())) selectOptionsItem.hidden = false; else selectOptionsItem.hidden = true;
                });
                selectOptions.hidden === true ? _this.selectAction(selectItem) : null;
            });
        }
        selectCallback(selectItem, originalSelect) {
            document.dispatchEvent(new CustomEvent("selectCallback", {
                detail: {
                    select: originalSelect
                }
            }));
        }
    }
    modules_flsModules.select = new SelectConstructor({});
    class DynamicAdapt {
        constructor(type) {
            this.type = type;
        }
        init() {
            this.оbjects = [];
            this.daClassname = "_dynamic_adapt_";
            this.nodes = [ ...document.querySelectorAll("[data-da]") ];
            this.nodes.forEach(node => {
                const data = node.dataset.da.trim();
                const dataArray = data.split(",");
                const оbject = {};
                оbject.element = node;
                оbject.parent = node.parentNode;
                оbject.destination = document.querySelector(`${dataArray[0].trim()}`);
                оbject.breakpoint = dataArray[1] ? dataArray[1].trim() : "767.98";
                оbject.place = dataArray[2] ? dataArray[2].trim() : "last";
                оbject.index = this.indexInParent(оbject.parent, оbject.element);
                this.оbjects.push(оbject);
            });
            this.arraySort(this.оbjects);
            this.mediaQueries = this.оbjects.map(({breakpoint}) => `(${this.type}-width: ${breakpoint / 16}em),${breakpoint}`).filter((item, index, self) => self.indexOf(item) === index);
            this.mediaQueries.forEach(media => {
                const mediaSplit = media.split(",");
                const matchMedia = window.matchMedia(mediaSplit[0]);
                const mediaBreakpoint = mediaSplit[1];
                const оbjectsFilter = this.оbjects.filter(({breakpoint}) => breakpoint === mediaBreakpoint);
                matchMedia.addEventListener("change", () => {
                    this.mediaHandler(matchMedia, оbjectsFilter);
                });
                this.mediaHandler(matchMedia, оbjectsFilter);
            });
        }
        mediaHandler(matchMedia, оbjects) {
            if (matchMedia.matches) оbjects.forEach(оbject => {
                this.moveTo(оbject.place, оbject.element, оbject.destination);
            }); else оbjects.forEach(({parent, element, index}) => {
                if (element.classList.contains(this.daClassname)) this.moveBack(parent, element, index);
            });
        }
        moveTo(place, element, destination) {
            element.classList.add(this.daClassname);
            if (place === "last" || place >= destination.children.length) {
                destination.append(element);
                return;
            }
            if (place === "first") {
                destination.prepend(element);
                return;
            }
            destination.children[place].before(element);
        }
        moveBack(parent, element, index) {
            element.classList.remove(this.daClassname);
            if (parent.children[index] !== void 0) parent.children[index].before(element); else parent.append(element);
        }
        indexInParent(parent, element) {
            return [ ...parent.children ].indexOf(element);
        }
        arraySort(arr) {
            if (this.type === "min") arr.sort((a, b) => {
                if (a.breakpoint === b.breakpoint) {
                    if (a.place === b.place) return 0;
                    if (a.place === "first" || b.place === "last") return -1;
                    if (a.place === "last" || b.place === "first") return 1;
                    return 0;
                }
                return a.breakpoint - b.breakpoint;
            }); else {
                arr.sort((a, b) => {
                    if (a.breakpoint === b.breakpoint) {
                        if (a.place === b.place) return 0;
                        if (a.place === "first" || b.place === "last") return 1;
                        if (a.place === "last" || b.place === "first") return -1;
                        return 0;
                    }
                    return b.breakpoint - a.breakpoint;
                });
                return;
            }
        }
    }
    const da = new DynamicAdapt("max");
    da.init();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const escapeHtml = value => {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value).replace(/[&<>"']/g, match => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[match] ?? match));
    };

    const renderTextWithBreaks = value => escapeHtml(value).replace(/\n/g, '<br>');

    const supportBtn = document.querySelector("[data-support-btn]");
    const supportWindow = document.querySelector("[data-support-window]");
    if (supportBtn && supportWindow) {
        const closeWindow = supportWindow.querySelector(".chat__close");
        const messagesContainer = supportWindow.querySelector("[data-support-messages]");
        const fetchUrl = supportWindow.dataset.supportFetchUrl || '';
        const sendUrl = supportWindow.dataset.supportSendUrl || '';
        const errorBox = supportWindow.querySelector('[data-support-error]');
        const form = supportWindow.querySelector('[data-support-form]');
        const input = form ? (form.querySelector('input[name="message"]') || form.querySelector('.chat__input')) : null;

        let isLoadingClientMessages = false;

        const showClientError = text => {
            if (!errorBox) {
                return;
            }
            if (text) {
                errorBox.textContent = text;
                errorBox.style.display = 'inline';
            } else {
                errorBox.textContent = '';
                errorBox.style.display = 'none';
            }
        };

        const scrollClientMessages = () => {
            if (messagesContainer) {
                requestAnimationFrame(() => {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                });
            }
        };

        const renderClientMessages = messages => {
            if (!messagesContainer) {
                return;
            }

            messagesContainer.innerHTML = '';

            if (!messages || !messages.length) {
                const empty = document.createElement('div');
                empty.className = 'chat__item';
                empty.textContent = supportWindow.dataset.supportEmptyText || 'There are no messages yet.';
                messagesContainer.appendChild(empty);
                return;
            }

            messages.forEach(message => {
                const bubble = document.createElement('div');
                bubble.className = 'chat__item' + (message.direction === 'outbound' ? ' chat__item--answer' : '');

                const wrapper = document.createElement('div');
                wrapper.className = 'chat__item-content';

                const textSpan = document.createElement('span');
                textSpan.className = 'chat__item-text';
                textSpan.innerHTML = renderTextWithBreaks(message.message ?? '');
                wrapper.appendChild(textSpan);

                if (message.created_at) {
                    const timeSpan = document.createElement('span');
                    timeSpan.className = 'chat__item-time';
                    timeSpan.textContent = message.created_at;
                    wrapper.appendChild(timeSpan);
                }

                bubble.appendChild(wrapper);
                messagesContainer.appendChild(bubble);
            });
        };

        const loadClientMessages = async (options = {}) => {
            if (!fetchUrl || isLoadingClientMessages) {
                return;
            }

            isLoadingClientMessages = true;

            try {
                const response = await fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    renderClientMessages(data.messages || []);
                    if (!options.silent) {
                        scrollClientMessages();
                    }
                }
            } catch (error) {
                if (!options.silent) {
                    showClientError('Не удалось получить сообщения.');
                }
            } finally {
                isLoadingClientMessages = false;
            }
        };

        const sendClientMessage = async () => {
            if (!form || !sendUrl) {
                return;
            }

            const value = input ? input.value.trim() : '';
            if (!value) {
                showClientError('Введите сообщение.');
                return;
            }

            const formData = new FormData(form);
            if (!formData.has('message')) {
                formData.set('message', value);
            }

            showClientError('');

            try {
                const response = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    renderClientMessages(data.messages || []);
                    if (input) {
                        input.value = '';
                    }
                    showClientError('');
                    scrollClientMessages();
                } else if (response.status === 422) {
                    const data = await response.json().catch(() => null);
                    const errors = data?.errors;
                    const errorMessage = data?.message || (errors ? Object.values(errors)[0]?.[0] : null) || 'Не удалось отправить сообщение.';
                    showClientError(errorMessage);
                } else {
                    showClientError('Не удалось отправить сообщение.');
                }
            } catch (error) {
                showClientError('Не удалось отправить сообщение.');
            }
        };

        if (form) {
            form.addEventListener('submit', event => {
                event.preventDefault();
                sendClientMessage();
            });
        }

        const openSupport = () => {
            supportWindow.style.display = "flex";
            requestAnimationFrame(() => {
                supportWindow.classList.add("show");
                loadClientMessages({ silent: true });
                scrollClientMessages();
            });
        };

        const closeSupport = () => {
            supportWindow.classList.remove("show");
            supportWindow.addEventListener("transitionend", () => {
                supportWindow.style.display = "none";
            }, {
                once: true
            });
        };

        const toggleSupport = () => {
            if (supportWindow.classList.contains("show")) {
                closeSupport();
            } else {
                openSupport();
            }
        };

        closeWindow?.addEventListener("click", toggleSupport);
        supportBtn.addEventListener("click", toggleSupport);

        loadClientMessages({ silent: true });

        if (supportWindow.dataset.supportOpen === "true") {
            openSupport();
        }

        setInterval(() => {
            if (supportWindow.classList.contains('show')) {
                loadClientMessages({ silent: true });
            }
        }, 5000);
    }
    document.querySelectorAll(".mobile-nav-tabs button")?.forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".mobile-nav-tabs button").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            document.querySelector(".main").classList.remove("active");
            document.querySelector(".aside").classList.remove("active");
            document.querySelector("." + btn.dataset.tab).classList.add("active");
        });
    });
    function initFileUploads() {
        const uploadContainers = document.querySelectorAll("[data-file-upload]");
        if (!uploadContainers.length) return;
        const formatCount = count => {
            if (count === 1) return "1 файл выбран";
            if (count >= 2 && count <= 4) return `${count} файла выбрано`;
            return `${count} файлов выбрано`;
        };
        const formatSize = bytes => {
            if (!bytes && bytes !== 0) return "";
            const units = ["Б", "КБ", "МБ", "ГБ"];
            let size = bytes;
            let unitIndex = 0;
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex += 1;
            }
            return `${size >= 10 || unitIndex === 0 ? Math.round(size) : size.toFixed(1)} ${units[unitIndex]}`;
        };
        uploadContainers.forEach(container => {
            const input = container.querySelector("[data-file-upload-input]");
            if (!input) return;
            const label = container.querySelector("[data-file-upload-label]");
            const list = container.querySelector("[data-file-upload-list]");
            const defaultLabel = label ? label.textContent.trim() : "";
            const objectUrls = [];
            let filesState = [];
            const canModifyFileList = typeof DataTransfer !== "undefined";
            const maxFilesAttr = parseInt(input.getAttribute("data-max-files"), 10);
            const maxFiles = Number.isFinite(maxFilesAttr) && maxFilesAttr > 0 ? maxFilesAttr : null;
            const documentListSelector = container.dataset.fileUploadDocList || "";
            const documentEmptySelector = container.dataset.fileUploadDocEmpty || "";
            const documentList = documentListSelector ? document.querySelector(documentListSelector) : null;
            const documentEmpty = documentEmptySelector ? document.querySelector(documentEmptySelector) : null;
            const revokePreviews = () => {
                objectUrls.splice(0).forEach(url => URL.revokeObjectURL(url));
            };
            const normalizeFiles = files => {
                const unique = [];
                const seen = new Set();
                files.forEach(file => {
                    const key = `${file.name}|${file.size}|${file.lastModified}`;
                    if (!seen.has(key)) {
                        seen.add(key);
                        unique.push(file);
                    }
                });
                if (maxFiles !== null && unique.length > maxFiles) {
                    return unique.slice(0, maxFiles);
                }
                return unique;
            };
            const syncInput = () => {
                if (!canModifyFileList) {
                    if (!filesState.length) {
                        input.value = "";
                    }
                    return;
                }
                const dataTransfer = new DataTransfer();
                filesState.forEach(file => dataTransfer.items.add(file));
                input.files = dataTransfer.files;
            };
            const removeDocumentPreviews = () => {
                if (!documentList) return;
                documentList.querySelectorAll('[data-document-preview]').forEach(el => el.remove());
            };
            const updateDocumentEmptyState = () => {
                if (!documentEmpty) return;
                const hasDocuments = documentList ? documentList.querySelectorAll('[data-document-entry]').length > 0 : false;
                const hasPreviews = filesState.length > 0;
                documentEmpty.style.display = hasDocuments || hasPreviews ? 'none' : '';
            };
            const removeFileAtIndex = index => {
                if (index < 0) return;
                if (!canModifyFileList && input.files && input.files.length) {
                    filesState = [];
                    input.value = "";
                } else {
                    filesState = normalizeFiles(filesState.filter((_, fileIndex) => fileIndex !== index));
                }
                syncInput();
                updateView();
            };
            const buildPreview = (file, index) => {
                const item = document.createElement('li');
                item.className = 'modal-content__file-item modal-content__file-item--preview';

                const info = document.createElement('div');
                info.className = 'modal-content__file-info';

                const thumb = document.createElement('div');
                thumb.className = 'modal-content__file-thumb';

                if (file.type && file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    objectUrls.push(url);
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.loading = 'lazy';
                    thumb.innerHTML = '';
                    thumb.appendChild(img);
                } else {
                    const ext = (file.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
                    const icon = document.createElement('span');
                    icon.className = 'modal-content__file-icon';
                    icon.textContent = ext;
                    thumb.innerHTML = '';
                    thumb.appendChild(icon);
                }

                const meta = document.createElement('div');
                meta.className = 'modal-content__file-meta';

                const name = document.createElement('span');
                name.className = 'modal-content__file-name';
                name.textContent = file.name;

                const size = document.createElement('span');
                size.className = 'modal-content__file-size';
                size.textContent = formatSize(file.size);

                const badge = document.createElement('span');
                badge.className = 'modal-content__file-chip';
                badge.textContent = 'Новый документ';

                meta.appendChild(name);
                if (size.textContent) {
                    meta.appendChild(size);
                }
                meta.appendChild(badge);

                info.appendChild(thumb);
                info.appendChild(meta);

                const actions = document.createElement('div');
                actions.className = 'modal-content__file-actions';

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn--light modal-content__file-remove-btn';
                remove.textContent = 'Удалить';
                remove.addEventListener('click', () => removeFileAtIndex(index));

                actions.appendChild(remove);

                item.appendChild(info);
                item.appendChild(actions);

                return item;
            };
            const createDocumentPreview = (file, index) => {
                const item = document.createElement('li');
                item.dataset.documentPreview = 'true';
                item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;';

                const info = document.createElement('div');
                info.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';

                const thumb = document.createElement('div');
                thumb.style.cssText = 'width: 64px; height: 64px; border-radius: 0.75rem; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(99,102,241,.08); color: #312e81; font-weight: 600; font-size: .75rem; text-transform: uppercase; border: 1px solid rgba(148, 163, 184, .35);';

                if (file.type && file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    objectUrls.push(url);
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                    thumb.innerHTML = '';
                    thumb.appendChild(img);
                } else {
                    const ext = (file.name.split('.').pop() || 'FILE').toUpperCase().slice(0, 4);
                    thumb.textContent = ext;
                }

                const meta = document.createElement('div');
                meta.style.cssText = 'display: flex; flex-direction: column; gap: 0.2rem;';

                const name = document.createElement('div');
                name.style.cssText = 'font-weight: 600; color: #0f172a;';
                name.textContent = file.name;

                const size = document.createElement('div');
                size.style.cssText = 'font-size: 0.8rem; color: #475569;';
                size.textContent = formatSize(file.size);

                const badge = document.createElement('span');
                badge.style.cssText = 'display: inline-flex; align-items: center; justify-content: center; padding: 0.2rem 0.6rem; border-radius: 999px; background: #eef2ff; color: #312e81; font-size: 0.75rem; font-weight: 600; width: fit-content;';
                badge.textContent = 'Новый документ';

                meta.appendChild(name);
                if (size.textContent) {
                    meta.appendChild(size);
                }
                meta.appendChild(badge);

                info.appendChild(thumb);
                info.appendChild(meta);

                const actions = document.createElement('div');
                actions.style.cssText = 'display: flex; align-items: center; gap: 0.5rem;';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn--md';
                removeBtn.style.background = '#F1F5F9';
                removeBtn.style.color = '#111827';
                removeBtn.textContent = 'Удалить';
                removeBtn.addEventListener('click', () => removeFileAtIndex(index));

                actions.appendChild(removeBtn);

                item.appendChild(info);
                item.appendChild(actions);

                return item;
            };
            const renderDocumentPreview = () => {
                if (!documentList) {
                    updateDocumentEmptyState();
                    return;
                }
                revokePreviews();
                removeDocumentPreviews();
                const anchor = documentList.querySelector('[data-document-entry]');
                filesState.forEach((file, index) => {
                    const previewItem = createDocumentPreview(file, index);
                    if (anchor) {
                        documentList.insertBefore(previewItem, anchor);
                    } else {
                        documentList.appendChild(previewItem);
                    }
                });
                updateDocumentEmptyState();
            };
            const updateView = () => {
                const files = filesState;
                if (label) {
                    if (documentList) {
                        label.textContent = defaultLabel || "Прикрепить файлы";
                    } else if (!files.length) {
                        label.textContent = defaultLabel || "Прикрепить файлы";
                    } else if (files.length === 1) {
                        label.textContent = files[0].name;
                    } else {
                        label.textContent = formatCount(files.length);
                    }
                }
                if (list) {
                    if (!documentList) {
                        revokePreviews();
                        list.innerHTML = "";
                        files.forEach((file, index) => {
                            list.appendChild(buildPreview(file, index));
                        });
                    } else {
                        list.innerHTML = "";
                    }
                }
                renderDocumentPreview();
            };
            const resetView = () => {
                filesState = [];
                syncInput();
                input.value = "";
                revokePreviews();
                if (label) label.textContent = defaultLabel || "Прикрепить файлы";
                if (list) list.innerHTML = "";
                removeDocumentPreviews();
                updateDocumentEmptyState();
            };
            input.addEventListener("change", () => {
                const selected = Array.from(input.files || []);
                if (!selected.length) {
                    return;
                }
                if (canModifyFileList) {
                    filesState = normalizeFiles(filesState.concat(selected));
                    input.value = "";
                    syncInput();
                } else {
                    filesState = normalizeFiles(selected);
                    input.value = "";
                }
                updateView();
            });
            const form = container.closest("form");
            if (form) {
                form.addEventListener("reset", () => {
                    setTimeout(resetView, 0);
                });
            }
            resetView();
        });
    }
    const initDashboardInteractions = () => {
        const withdrawal = document.querySelector(".withdrawal");
        const transactionScopes = document.querySelectorAll('[data-transaction-scope]');
        transactionScopes.forEach(scope => {
            const tabs = scope.querySelectorAll('[data-transaction-tab]');
            if (!tabs.length) {
                return;
            }
            const panels = scope.querySelectorAll('[data-transaction-panel]');
            const activate = name => {
                tabs.forEach(btn => {
                    const isActive = btn.dataset.transactionTab === name;
                    btn.classList.toggle('transaction-tab--active', isActive);
                    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                panels.forEach(panel => {
                    const isMatch = panel.dataset.transactionPanel === name;
                    panel.classList.toggle('is-active', isMatch);
                    panel.hidden = !isMatch;
                });
            };
            tabs.forEach(btn => {
                btn.addEventListener('click', () => activate(btn.dataset.transactionTab));
            });
            activate('transactions');
        });

        if (withdrawal) {
            const backBtn = withdrawal.querySelector(".withdrawal__back");
            const blocks = withdrawal.querySelectorAll(".withdrawal__block");
            const navBlock = withdrawal.querySelector("[data-withdraw-nav]");
            const btns = withdrawal.querySelectorAll(".withdrawal__btn");
            backBtn.style.display = "none";
            blocks.forEach(b => {
                if (b !== navBlock) b.style.display = "none";
            });
            btns.forEach(btn => {
                btn.addEventListener("click", () => {
                    const target = btn.dataset.target;
                    blocks.forEach(b => b.style.display = "none");
                    withdrawal.querySelector(`[data-withdraw-${target}]`).style.display = "flex";
                    navBlock.style.display = "none";
                    backBtn.style.display = "inline-flex";
                    btns.forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                });
            });
            const initialMethod = withdrawal.dataset.initialMethod;
            if (initialMethod) {
                const initialButton = Array.from(btns).find(btn => btn.dataset.target === initialMethod);
                if (initialButton) {
                    initialButton.click();
                }
            }
            backBtn.addEventListener("click", () => {
                blocks.forEach(b => {
                    if (b !== navBlock) b.style.display = "none";
                });
                navBlock.style.display = "flex";
                backBtn.style.display = "none";
            });
        }

        const adminPanel = document.querySelector('.admin-panel');
        if (adminPanel) {
            const scrollStorageKey = 'admin-dashboard-scroll';
            const trackedForms = new Set();
            const collectForms = (root) => {
                if (!root) {
                    return;
                }
                root.querySelectorAll('form').forEach((form) => {
                    if (form.hasAttribute('data-ignore-scroll')) {
                        return;
                    }

                    const method = (form.getAttribute('method') || form.method || 'get').toLowerCase();

                    if (method === 'get' && ! form.hasAttribute('data-preserve-scroll')) {
                        return;
                    }

                    trackedForms.add(form);
                });
            };

            collectForms(adminPanel);
            document.querySelectorAll('.popup').forEach((popup) => collectForms(popup));
            document.querySelectorAll('form[data-preserve-scroll]').forEach((form) => trackedForms.add(form));

            const persistScroll = () => {
                sessionStorage.setItem(scrollStorageKey, String(window.scrollY || 0));
            };

            trackedForms.forEach((form) => {
                form.addEventListener('submit', persistScroll, { capture: true });
            });

            const restoreScroll = () => {
                const storedValue = sessionStorage.getItem(scrollStorageKey);
                if (storedValue === null) {
                    return;
                }

                sessionStorage.removeItem(scrollStorageKey);

                const numericValue = Number.parseFloat(storedValue);
                if (Number.isNaN(numericValue)) {
                    return;
                }

                requestAnimationFrame(() => {
                    window.scrollTo(0, numericValue);
                });
            };

            window.addEventListener('pageshow', () => {
                restoreScroll();
            });

            restoreScroll();
        }

        initFileUploads();
        tabs();

        const deleteUserToggle = document.querySelector('[data-action="delete-user-toggle"]');
        const deleteUserConfirm = document.querySelector('[data-role="delete-user-confirm"]');
        if (deleteUserToggle && deleteUserConfirm) {
            const deleteUserCancel = deleteUserConfirm.querySelector('[data-action="delete-user-cancel"]');
            const showConfirm = () => {
                deleteUserToggle.style.display = 'none';
                deleteUserConfirm.style.display = 'block';
            };
            const hideConfirm = () => {
                deleteUserConfirm.style.display = 'none';
                deleteUserToggle.style.display = '';
            };
            deleteUserToggle.addEventListener('click', showConfirm);
            if (deleteUserCancel) {
                deleteUserCancel.addEventListener('click', hideConfirm);
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardInteractions);
    } else {
        initDashboardInteractions();
    }
})();
