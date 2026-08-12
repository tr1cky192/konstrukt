(function () {

    let state = {
        site_id: 'default',
        settings: {
            title: 'Мій Лендінг',
            font_heading: 'Outfit',
            font_body: 'Inter',
            color_primary: '#6366f1',
            color_secondary: '#06b6d4',
            color_bg: '#ffffff',
            color_text: '#1f2937',
            color_card_bg: '#f9fafb',
            payment_key: '',
            payment_secret: ''
        },
        blocks: []
    };

    let activeBlockId = null;
    let saveTimeout = null;

    if (window.INITIAL_SITE_CONFIG) {
        state = window.INITIAL_SITE_CONFIG;
        if (!state.settings) state.settings = {};
        if (!state.blocks) state.blocks = [];
    }

    const selectors = {
        saveBtn: document.getElementById('btn-save'),
        previewLink: document.getElementById('btn-preview-link'),
        viewportBtns: document.querySelectorAll('.viewport-btn'),
        canvasWrapper: document.getElementById('canvas-wrapper'),
        previewIframe: document.getElementById('preview-iframe'),
        tabBtns: document.querySelectorAll('.tab-btn'),
        tabPanels: document.querySelectorAll('.tab-panel'),
        activeBlocksList: document.getElementById('active-blocks-list'),
        blockEditorContainer: document.getElementById('block-editor-container'),
        templateCards: document.querySelectorAll('.add-block-card'),
        toastContainer: document.getElementById('toast-container'),


        primaryColor: document.getElementById('color-primary'),
        primaryColorText: document.getElementById('color-primary-text'),
        secondaryColor: document.getElementById('color-secondary'),
        secondaryColorText: document.getElementById('color-secondary-text'),
        bgColor: document.getElementById('color-bg'),
        bgColorText: document.getElementById('color-bg-text'),
        textColor: document.getElementById('color-text'),
        textColorText: document.getElementById('color-text-text'),
        cardBgColor: document.getElementById('color-card-bg'),
        cardBgColorText: document.getElementById('color-card-bg-text'),
        fontHeading: document.getElementById('font-heading'),
        fontBody: document.getElementById('font-body'),


        siteSlug: document.getElementById('site-slug-id'),
        siteSlugDisplay: document.getElementById('site-slug-display'),
        sitePageTitle: document.getElementById('site-page-title'),
        paymentKey: document.getElementById('payment-merchant-key'),
        paymentSecret: document.getElementById('payment-merchant-secret')
    };


    function init() {
        setupTabs();
        setupViewports();
        setupTemplateSelector();
        setupGlobalSettingsBindings();
        populateSidebarSettings();
        renderActiveBlocksList();


        selectors.saveBtn.addEventListener('click', () => {
            saveStateToServer(true);
        });


        selectors.siteSlug.addEventListener('input', (e) => {
            let slug = e.target.value.replace(/[^a-zA-Z0-9_-]/g, '');
            selectors.siteSlug.value = slug;
            selectors.siteSlugDisplay.textContent = slug || 'default';
            state.site_id = slug || 'default';

  
            selectors.previewLink.href = state.site_id;
            debounceSave();
        });


        selectors.previewIframe.addEventListener('load', setupIframeControls);
    }


    function setupViewports() {
        selectors.viewportBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                selectors.viewportBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const viewport = btn.getAttribute('data-viewport');
                selectors.canvasWrapper.className = `canvas-wrapper ${viewport}`;
            });
        });
    }


    function setupTabs() {
        selectors.tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                switchTab(tabId);
            });
        });
    }

    function switchTab(tabId) {
        selectors.tabBtns.forEach(b => {
            b.classList.remove('active');
            if (b.getAttribute('data-tab') === tabId) {
                b.classList.add('active');
            }
        });

        selectors.tabPanels.forEach(p => {
            p.classList.remove('active');
            if (p.id === `tab-${tabId}`) {
                p.classList.add('active');
            }
        });
    }


    function setupGlobalSettingsBindings() {

        const bindColorInput = (colorEl, textEl, key) => {
            if (colorEl) {
                colorEl.addEventListener('input', (e) => {
                    if (textEl) textEl.value = e.target.value;
                    state.settings[key] = e.target.value;
                    debounceSave();
                });
            }
            if (textEl) {
                textEl.addEventListener('input', (e) => {
                    let color = e.target.value;
                    if (/^#[0-9A-F]{6}$/i.test(color)) {
                        if (colorEl) colorEl.value = color;
                        state.settings[key] = color;
                        debounceSave();
                    }
                });
            }
        };

        bindColorInput(selectors.primaryColor, selectors.primaryColorText, 'color_primary');
        bindColorInput(selectors.secondaryColor, selectors.secondaryColorText, 'color_secondary');
        bindColorInput(selectors.bgColor, selectors.bgColorText, 'color_bg');
        bindColorInput(selectors.textColor, selectors.textColorText, 'color_text');
        bindColorInput(selectors.cardBgColor, selectors.cardBgColorText, 'color_card_bg');


        if (selectors.fontHeading) {
            selectors.fontHeading.addEventListener('change', (e) => {
                state.settings.font_heading = e.target.value;
                debounceSave();
            });
        }
        if (selectors.fontBody) {
            selectors.fontBody.addEventListener('change', (e) => {
                state.settings.font_body = e.target.value;
                debounceSave();
            });
        }


        if (selectors.sitePageTitle) {
            selectors.sitePageTitle.addEventListener('input', (e) => {
                state.settings.title = e.target.value;
                debounceSave();
            });
        }
        if (selectors.paymentKey) {
            selectors.paymentKey.addEventListener('input', (e) => {
                state.settings.payment_key = e.target.value;
                debounceSave();
            });
        }
        if (selectors.paymentSecret) {
            selectors.paymentSecret.addEventListener('input', (e) => {
                state.settings.payment_secret = e.target.value;
                debounceSave();
            });
        }
    }

    function populateSidebarSettings() {
        if (selectors.siteSlug) selectors.siteSlug.value = state.site_id;
        if (selectors.siteSlugDisplay) selectors.siteSlugDisplay.textContent = state.site_id;
        if (selectors.sitePageTitle) selectors.sitePageTitle.value = state.settings.title || 'Мій Лендінг';

        if (selectors.primaryColor) selectors.primaryColor.value = state.settings.color_primary || '#6366f1';
        if (selectors.primaryColorText) selectors.primaryColorText.value = state.settings.color_primary || '#6366f1';
        if (selectors.secondaryColor) selectors.secondaryColor.value = state.settings.color_secondary || '#06b6d4';
        if (selectors.secondaryColorText) selectors.secondaryColorText.value = state.settings.color_secondary || '#06b6d4';
        if (selectors.bgColor) selectors.bgColor.value = state.settings.color_bg || '#ffffff';
        if (selectors.bgColorText) selectors.bgColorText.value = state.settings.color_bg || '#ffffff';
        if (selectors.textColor) selectors.textColor.value = state.settings.color_text || '#1f2937';
        if (selectors.textColorText) selectors.textColorText.value = state.settings.color_text || '#1f2937';
        if (selectors.cardBgColor) selectors.cardBgColor.value = state.settings.color_card_bg || '#f9fafb';
        if (selectors.cardBgColorText) selectors.cardBgColorText.value = state.settings.color_card_bg || '#f9fafb';

        if (selectors.fontHeading) selectors.fontHeading.value = state.settings.font_heading || 'Outfit';
        if (selectors.fontBody) selectors.fontBody.value = state.settings.font_body || 'Inter';

        if (selectors.paymentKey) selectors.paymentKey.value = state.settings.payment_key || '';
        if (selectors.paymentSecret) selectors.paymentSecret.value = state.settings.payment_secret || '';

        if (selectors.previewLink) selectors.previewLink.href = state.site_id;
    }

    function renderActiveBlocksList() {
        selectors.activeBlocksList.innerHTML = '';

        if (state.blocks.length === 0) {
            selectors.activeBlocksList.innerHTML = '<div style="color: var(--panel-text-muted); font-size: 0.85rem; text-align: center; padding: 1rem 0;">Сайт поки що порожній. Додайте блок нижче!</div>';
            return;
        }

        state.blocks.forEach((block, index) => {
            const blockItem = document.createElement('div');
            blockItem.className = 'active-block-item';

            let icon = 'B';
            if (block.type === 'header') icon = 'HD';
            if (block.type === 'footer') icon = 'FT';
            if (block.type === 'features') icon = 'FE';
            if (block.type === 'showcase') icon = 'SC';
            if (block.type === 'pricing') icon = 'PR';
            if (block.type === 'testimonials') icon = 'TS';

            const blockTitle = block.type.charAt(0).toUpperCase() + block.type.slice(1);

            blockItem.innerHTML = `
                <div class="active-block-name">
                    <span style="color: var(--accent); font-weight: 800; font-size: 0.75rem; background: rgba(99,102,241,0.1); width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;">${icon}</span>
                    <span>${block.title || blockTitle}</span>
                </div>
                <div class="active-block-actions">
                    <button class="action-btn btn-up" data-index="${index}" title="Вгору">▲</button>
                    <button class="action-btn btn-down" data-index="${index}" title="Вниз">▼</button>
                    <button class="action-btn btn-edit" data-id="${block.id}" title="Редагувати">✎</button>
                    <button class="action-btn btn-delete" data-index="${index}" title="Видалити">✖</button>
                </div>
            `;

            blockItem.querySelector('.btn-up').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                moveBlock(index, -1);
            });
            blockItem.querySelector('.btn-down').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                moveBlock(index, 1);
            });
            blockItem.querySelector('.btn-edit').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                editBlock(block.id);
            });
            blockItem.querySelector('.btn-delete').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                deleteBlock(index);
            });

            selectors.activeBlocksList.appendChild(blockItem);
        });
    }

    function moveBlock(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= state.blocks.length) return;

        const temp = state.blocks[index];
        state.blocks[index] = state.blocks[newIndex];
        state.blocks[newIndex] = temp;

        renderActiveBlocksList();
        saveStateToServer(false);
    }

    function deleteBlock(index) {
        console.log('deleteBlock called for index:', index);
        if (index < 0 || index >= state.blocks.length) {
            console.warn('deleteBlock: Index out of bounds:', index);
            return;
        }

        const blockId = state.blocks[index].id;
        state.blocks.splice(index, 1);
        if (activeBlockId === blockId) {
            activeBlockId = null;
            selectors.blockEditorContainer.innerHTML = '<div style="color: var(--panel-text-muted); text-align: center; padding: 3rem 0;">Оберіть блок для редагування.</div>';
        }
        renderActiveBlocksList();
        saveStateToServer(false);
        console.log('Block deleted successfully. Remaining blocks:', state.blocks.length);
    }

    function setupIframeControls() {
        const iframe = selectors.previewIframe;
        if (!iframe) return;

        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        if (!iframeDoc) return;


        if (iframeDoc.getElementById('editor-injected-styles')) return;


        const style = iframeDoc.createElement('style');
        style.id = 'editor-injected-styles';
        style.textContent = `
            .block-relative-container {
                position: relative !important;
            }
            .block-relative-container:hover {
                outline: 2px dashed #ff4800 !important;
                outline-offset: -2px;
            }
            .editor-block-control-overlay {
                position: absolute;
                top: 10px;
                right: 10px;
                background: #ffffff !important;
                border: 1px solid rgba(0, 0, 0, 0.15) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
                border-radius: 8px !important;
                display: flex !important;
                gap: 4px !important;
                padding: 4px !important;
                z-index: 99999 !important;
                opacity: 0 !important;
                transition: opacity 0.2s, transform 0.2s !important;
                transform: translateY(-5px) !important;
                pointer-events: auto !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }
            .block-relative-container:hover .editor-block-control-overlay {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
            .editor-block-btn {
                width: 28px !important;
                height: 28px !important;
                border-radius: 6px !important;
                border: none !important;
                background: #ffffff !important;
                color: #333333 !important;
                font-size: 12px !important;
                font-weight: bold !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.2s !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            }
            .editor-block-btn:hover {
                background: #ff4800 !important;
                color: #ffffff !important;
            }
            .editor-block-btn.btn-del:hover {
                background: #ef4444 !important;
                color: #ffffff !important;
            }
        `;
        iframeDoc.head.appendChild(style);


        const blockElements = iframeDoc.querySelectorAll('header, section, footer');
        blockElements.forEach(el => {

            const blockId = el.getAttribute('id');
            if (!blockId) return;

            const blockIndex = state.blocks.findIndex(b => b.id === blockId);
            if (blockIndex === -1) return;

            el.classList.add('block-relative-container');


            const overlay = iframeDoc.createElement('div');
            overlay.className = 'editor-block-control-overlay';


            const btnUp = iframeDoc.createElement('button');
            btnUp.className = 'editor-block-btn';
            btnUp.innerHTML = '▲';
            btnUp.title = 'Перемістити вгору';
            if (blockIndex === 0) btnUp.style.opacity = '0.3';
            btnUp.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (blockIndex > 0) {
                    moveBlock(blockIndex, -1);
                }
            });


            const btnDown = iframeDoc.createElement('button');
            btnDown.className = 'editor-block-btn';
            btnDown.innerHTML = '▼';
            btnDown.title = 'Перемістити вниз';
            if (blockIndex === state.blocks.length - 1) btnDown.style.opacity = '0.3';
            btnDown.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (blockIndex < state.blocks.length - 1) {
                    moveBlock(blockIndex, 1);
                }
            });


            const btnEdit = iframeDoc.createElement('button');
            btnEdit.className = 'editor-block-btn';
            btnEdit.innerHTML = '✎';
            btnEdit.title = 'Редагувати вміст';
            btnEdit.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                editBlock(blockId);
            });


            const btnDelete = iframeDoc.createElement('button');
            btnDelete.className = 'editor-block-btn btn-del';
            btnDelete.innerHTML = '✖';
            btnDelete.title = 'Видалити блок';
            btnDelete.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                deleteBlock(blockIndex);
            });

            overlay.appendChild(btnUp);
            overlay.appendChild(btnDown);
            overlay.appendChild(btnEdit);
            overlay.appendChild(btnDelete);
            el.appendChild(overlay);
        });
    }


    function setupTemplateSelector() {
        selectors.templateCards.forEach(card => {
            card.addEventListener('click', () => {
                const type = card.getAttribute('data-type');
                addBlock(type);
            });
        });
    }

    function addBlock(type) {
        const blockId = `${type}_${Date.now()}`;
        let newBlock = {
            id: blockId,
            type: type
        };


        switch (type) {
            case 'header':
                newBlock.title = 'Шапка';
                newBlock.logo_text = 'Компанія';
                newBlock.logo_img = '';
                newBlock.nav_links = 'Головна,Переваги,Тарифи';
                newBlock.cta_text = 'Купити';
                break;
            case 'hero':
                newBlock.title = 'Заголовок вашої пропозиції';
                newBlock.subtitle = 'Субтитр з описом переваг вашого продукту.';
                newBlock.image = '';
                newBlock.btn_primary_text = 'Придбати';
                newBlock.btn_secondary_text = 'Детальніше';
                break;
            case 'features':
                newBlock.title = 'Чому ми?';
                newBlock.subtitle = 'Наші ключові особливості';
                newBlock.items = [
                    { icon: '1', title: 'Швидкість', desc: 'Працюємо максимально швидко.' },
                    { icon: '2', title: 'Якість', desc: 'Високий рівень сервісу.' },
                    { icon: '3', title: 'Безпека', desc: 'Всі дані зашифровані.' }
                ];
                break;
            case 'showcase':
                newBlock.title = 'Опис товару';
                newBlock.desc = 'Детальний опис характеристик продукту.';
                newBlock.image = '';
                newBlock.bullets = ['Характеристика 1', 'Характеристика 2', 'Характеристика 3'];
                break;
            case 'pricing':
                newBlock.title = 'Купівля';
                newBlock.subtitle = 'Швидка оплата вашого замовлення';
                newBlock.product_name = 'Тариф Стандартний';
                newBlock.price = '399';
                newBlock.currency = '₴';
                newBlock.features = ['Повна підтримка', 'Якісний товар', 'Безкоштовна доставка'];
                newBlock.btn_text = 'Купити зараз';
                break;
            case 'testimonials':
                newBlock.title = 'Відгуки клієнтів';
                newBlock.subtitle = 'Що говорять про нас';
                newBlock.items = [
                    { rating: 5, quote: 'Дуже сподобалась якість продукту та сервіс!', name: 'Олена Петренко', role: 'Покупець', avatar: '' }
                ];
                break;
            case 'footer':
                newBlock.title = 'Підвал';
                newBlock.logo_text = 'Компанія';
                newBlock.logo_img = 'assets/logo.svg';
                newBlock.copy_text = `© ${new Date().getFullYear()} Компанія. Всі права захищені.`;
                break;
        }

        state.blocks.push(newBlock);
        renderActiveBlocksList();
        editBlock(blockId);
        saveStateToServer(false);
        showToast('Блок додано на сторінку', 'success');
    }

    function editBlock(blockId) {
        activeBlockId = blockId;
        const blockIndex = state.blocks.findIndex(b => b.id === blockId);
        if (blockIndex === -1) return;

        const block = state.blocks[blockIndex];
        switchTab('edit-block');


        selectors.blockEditorContainer.innerHTML = '';

        const formTitle = document.createElement('div');
        formTitle.className = 'panel-section-title';
        formTitle.innerHTML = `<span>Налаштування: ${block.title || block.type}</span>`;
        selectors.blockEditorContainer.appendChild(formTitle);


        const addTextInput = (label, propName, isTextarea = false) => {
            const group = document.createElement('div');
            group.className = 'form-group';
            group.innerHTML = `
                <label class="form-label">${label}</label>
                ${isTextarea
                    ? `<textarea class="form-textarea" rows="4" data-prop="${propName}">${block[propName] || ''}</textarea>`
                    : `<input type="text" class="form-input" data-prop="${propName}" value="${block[propName] || ''}">`
                }
            `;

            const input = group.querySelector('[data-prop]');
            input.addEventListener('input', (e) => {
                block[propName] = e.target.value;
                debounceSave();
            });

            selectors.blockEditorContainer.appendChild(group);
        };

        const addImageUploadInput = (label, propName) => {
            const group = document.createElement('div');
            group.className = 'form-group';

            const currentUrl = block[propName] || '';
            const hasImage = currentUrl !== '';

            group.innerHTML = `
                <div class="image-upload-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin-bottom: 0;">${label}</label>
                    <button type="button" class="btn-delete-image" id="delete-${propName}" style="display: ${hasImage ? 'inline-block' : 'none'}; background: none; border: none; color: #ef4444; font-size: 0.75rem; cursor: pointer; font-weight: 600; padding: 0;">
                        Видалити фото
                    </button>
                </div>
                <div class="image-upload-wrapper" id="upload-wrapper-${propName}">
                    <img src="${currentUrl}" class="image-upload-preview" id="preview-${propName}" style="display: ${hasImage ? 'block' : 'none'};">
                    <div class="image-upload-icon" id="icon-${propName}" style="display: ${hasImage ? 'none' : 'block'};">⬆</div>
                    <div class="image-upload-text" id="text-${propName}">${hasImage ? 'Змінити зображення' : 'Завантажити зображення'}</div>
                    <input type="file" id="file-${propName}" accept="image/*" style="display: none;">
                </div>
            `;

            const wrapper = group.querySelector('.image-upload-wrapper');
            const fileInput = group.querySelector('input[type="file"]');
            const previewImg = group.querySelector('.image-upload-preview');
            const iconEl = group.querySelector('.image-upload-icon');
            const textEl = group.querySelector('.image-upload-text');
            const deleteBtn = group.querySelector(`#delete-${propName}`);

            wrapper.addEventListener('click', () => {
                fileInput.click();
            });

            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                block[propName] = '';
                previewImg.removeAttribute('src');
                previewImg.style.display = 'none';
                iconEl.style.display = 'block';
                textEl.textContent = 'Завантажити зображення';
                deleteBtn.style.display = 'none';
                fileInput.value = '';
                debounceSave();
                showToast('Зображення видалено', 'success');
            });

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                textEl.textContent = 'Завантаження...';

                fetch((window.BASE_DIR || '') + '/api/upload.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            block[propName] = data.url;
                            previewImg.src = data.url;
                            previewImg.style.display = 'block';
                            iconEl.style.display = 'none';
                            textEl.textContent = 'Змінити зображення';
                            deleteBtn.style.display = 'inline-block';
                            debounceSave();
                            showToast('Зображення завантажено', 'success');
                        } else {
                            textEl.textContent = 'Помилка завантаження';
                            showToast(data.error || 'Помилка завантаження', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        textEl.textContent = 'Помилка завантаження';
                        showToast('Сталася помилка при завантаженні', 'error');
                    });
            });

            selectors.blockEditorContainer.appendChild(group);
        };


        if (block.type === 'header') {
            addTextInput('Текст логотипу', 'logo_text');
            addImageUploadInput('Зображення логотипу (за бажанням)', 'logo_img');
            addTextInput('Пункти меню (через кому)', 'nav_links');
            addTextInput('Текст кнопки дії', 'cta_text');
        }
        else if (block.type === 'hero') {
            addTextInput('Заголовок', 'title', true);
            addTextInput('Опис', 'subtitle', true);
            addImageUploadInput('Фонове зображення', 'image');
            addTextInput('Текст головної кнопки', 'btn_primary_text');
            addTextInput('Текст другорядної кнопки', 'btn_secondary_text');
        }
        else if (block.type === 'features') {
            addTextInput('Головний заголовок', 'title');
            addTextInput('Підзаголовок', 'subtitle', true);


            const itemsTitle = document.createElement('div');
            itemsTitle.className = 'panel-section-title';
            itemsTitle.style.marginTop = '1.5rem';
            itemsTitle.style.fontSize = '0.95rem';
            itemsTitle.textContent = 'Елементи переваг';
            selectors.blockEditorContainer.appendChild(itemsTitle);

            const itemsContainer = document.createElement('div');
            itemsContainer.style.display = 'flex';
            itemsContainer.style.flexDirection = 'column';
            itemsContainer.style.gap = '1rem';
            selectors.blockEditorContainer.appendChild(itemsContainer);

            const renderItems = () => {
                itemsContainer.innerHTML = '';
                block.items.forEach((item, itemIdx) => {
                    const itemBox = document.createElement('div');
                    itemBox.style.padding = '1rem';
                    itemBox.style.backgroundColor = 'rgba(255,255,255,0.02)';
                    itemBox.style.border = '1px solid var(--panel-border)';
                    itemBox.style.borderRadius = '8px';

                    itemBox.innerHTML = `
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Іконка або символ</label>
                            <input type="text" class="form-input" data-item-prop="icon" value="${item.icon || ''}" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Заголовок</label>
                            <input type="text" class="form-input" data-item-prop="title" value="${item.title || ''}" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Опис</label>
                            <textarea class="form-textarea" data-item-prop="desc" rows="2" style="padding: 0.5rem; font-size:0.85rem;">${item.desc || ''}</textarea>
                        </div>
                        <button class="editor-btn btn-editor-secondary btn-delete-item" style="padding:0.4rem 0.8rem; font-size:0.75rem; color:var(--danger); width:100%; justify-content:center; margin-top:0.5rem;">Видалити перевагу</button>
                    `;

                    itemBox.querySelector('[data-item-prop="icon"]').addEventListener('input', (e) => {
                        item.icon = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="title"]').addEventListener('input', (e) => {
                        item.title = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="desc"]').addEventListener('input', (e) => {
                        item.desc = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('.btn-delete-item').addEventListener('click', () => {
                        block.items.splice(itemIdx, 1);
                        renderItems();
                        debounceSave();
                    });

                    itemsContainer.appendChild(itemBox);
                });
            };

            renderItems();

            const addFeatureBtn = document.createElement('button');
            addFeatureBtn.className = 'editor-btn btn-editor-secondary';
            addFeatureBtn.style.marginTop = '1rem';
            addFeatureBtn.style.width = '100%';
            addFeatureBtn.textContent = '+ Додати перевагу';
            addFeatureBtn.addEventListener('click', () => {
                block.items.push({ icon: '✓', title: 'Нова перевага', desc: 'Детальний опис.' });
                renderItems();
                debounceSave();
            });
            selectors.blockEditorContainer.appendChild(addFeatureBtn);
        }
        else if (block.type === 'showcase') {
            addTextInput('Заголовок', 'title');
            addTextInput('Опис', 'desc', true);
            addImageUploadInput('Зображення продукту', 'image');


            const bulletsGroup = document.createElement('div');
            bulletsGroup.className = 'form-group';
            bulletsGroup.innerHTML = `
                <label class="form-label">Переваги продукту (по одній на рядок)</label>
                <textarea class="form-textarea" rows="4" id="bullets-textarea">${(block.bullets || []).join('\n')}</textarea>
            `;
            bulletsGroup.querySelector('textarea').addEventListener('input', (e) => {
                block.bullets = e.target.value.split('\n').map(s => s.trim()).filter(s => s !== '');
                debounceSave();
            });
            selectors.blockEditorContainer.appendChild(bulletsGroup);
        }
        else if (block.type === 'pricing') {
            addTextInput('Головний заголовок блоку', 'title');
            addTextInput('Підзаголовок', 'subtitle', true);
            addTextInput('Назва товару/пакету', 'product_name');
            addTextInput('Ціна', 'price');
            addTextInput('Валюта (символ або код)', 'currency');
            addTextInput('Текст кнопки оплати', 'btn_text');

            const pricingFeaturesGroup = document.createElement('div');
            pricingFeaturesGroup.className = 'form-group';
            pricingFeaturesGroup.innerHTML = `
                <label class="form-label">Особливості товару (по одній на рядок)</label>
                <textarea class="form-textarea" rows="4" id="pricing-features-textarea">${(block.features || []).join('\n')}</textarea>
            `;
            pricingFeaturesGroup.querySelector('textarea').addEventListener('input', (e) => {
                block.features = e.target.value.split('\n').map(s => s.trim()).filter(s => s !== '');
                debounceSave();
            });
            selectors.blockEditorContainer.appendChild(pricingFeaturesGroup);
        }
        else if (block.type === 'testimonials') {
            addTextInput('Головний заголовок', 'title');
            addTextInput('Підзаголовок', 'subtitle', true);


            const itemsTitle = document.createElement('div');
            itemsTitle.className = 'panel-section-title';
            itemsTitle.style.marginTop = '1.5rem';
            itemsTitle.style.fontSize = '0.95rem';
            itemsTitle.textContent = 'Картки відгуків';
            selectors.blockEditorContainer.appendChild(itemsTitle);

            const itemsContainer = document.createElement('div');
            itemsContainer.style.display = 'flex';
            itemsContainer.style.flexDirection = 'column';
            itemsContainer.style.gap = '1rem';
            selectors.blockEditorContainer.appendChild(itemsContainer);

            const renderItems = () => {
                itemsContainer.innerHTML = '';
                block.items.forEach((item, itemIdx) => {
                    const itemBox = document.createElement('div');
                    itemBox.style.padding = '1rem';
                    itemBox.style.backgroundColor = 'rgba(255,255,255,0.02)';
                    itemBox.style.border = '1px solid var(--panel-border)';
                    itemBox.style.borderRadius = '8px';

                    itemBox.innerHTML = `
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Оцінка (зірок)</label>
                            <select class="form-select" data-item-prop="rating" style="padding: 0.5rem; font-size:0.85rem;">
                                <option value="5" ${item.rating === 5 ? 'selected' : ''}>5 зірок</option>
                                <option value="4" ${item.rating === 4 ? 'selected' : ''}>4 зірки</option>
                                <option value="3" ${item.rating === 3 ? 'selected' : ''}>3 зірки</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Текст відгуку</label>
                            <textarea class="form-textarea" data-item-prop="quote" rows="2" style="padding: 0.5rem; font-size:0.85rem;">${item.quote || ''}</textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Ім'я клієнта</label>
                            <input type="text" class="form-input" data-item-prop="name" value="${item.name || ''}" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Роль / Посада</label>
                            <input type="text" class="form-input" data-item-prop="role" value="${item.role || ''}" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label" style="font-size: 0.75rem;">Аватар URL</label>
                            <input type="text" class="form-input" data-item-prop="avatar" value="${item.avatar || ''}" style="padding: 0.5rem;">
                        </div>
                        <button class="editor-btn btn-editor-secondary btn-delete-item" style="padding:0.4rem 0.8rem; font-size:0.75rem; color:var(--danger); width:100%; justify-content:center; margin-top:0.5rem;">Видалити відгук</button>
                    `;

                    itemBox.querySelector('[data-item-prop="rating"]').addEventListener('change', (e) => {
                        item.rating = parseInt(e.target.value);
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="quote"]').addEventListener('input', (e) => {
                        item.quote = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="name"]').addEventListener('input', (e) => {
                        item.name = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="role"]').addEventListener('input', (e) => {
                        item.role = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('[data-item-prop="avatar"]').addEventListener('input', (e) => {
                        item.avatar = e.target.value;
                        debounceSave();
                    });
                    itemBox.querySelector('.btn-delete-item').addEventListener('click', () => {
                        block.items.splice(itemIdx, 1);
                        renderItems();
                        saveStateToServer(false);
                    });

                    itemsContainer.appendChild(itemBox);
                });
            };

            renderItems();

            const addTestimonialBtn = document.createElement('button');
            addTestimonialBtn.className = 'editor-btn btn-editor-secondary';
            addTestimonialBtn.style.marginTop = '1rem';
            addTestimonialBtn.style.width = '100%';
            addTestimonialBtn.textContent = '+ Додати відгук';
            addTestimonialBtn.addEventListener('click', () => {
                block.items.push({ rating: 5, quote: 'Чудово!', name: 'Новий клієнт', role: 'Покупець', avatar: '' });
                renderItems();
                saveStateToServer(false);
            });
            selectors.blockEditorContainer.appendChild(addTestimonialBtn);
        }
        else if (block.type === 'footer') {
            addTextInput('Текст логотипу підвалу', 'logo_text');
            addImageUploadInput('Зображення логотипу', 'logo_img');
            addTextInput('Текст копірайту', 'copy_text');
        }
    }


    function debounceSave() {
        selectors.saveBtn.textContent = 'Збереження...';

        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            saveStateToServer(false);
        }, 800); 
    }

    function saveStateToServer(isManual = false) {
        return fetch((window.BASE_DIR || '') + '/api/save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(state)
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    selectors.saveBtn.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Зберегти зміни
                `;

  
                    selectors.previewIframe.src = `view.php?id=${state.site_id}&v=${Date.now()}`;


                    if (isManual) {
                        showToast('Конфігурацію збережено успішно!', 'success');
                    }
                } else {
                    showToast(data.error || 'Помилка при збереженні', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Помилка мережі при збереженні', 'error');
            });
    }


    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        let icon = '✓';
        if (type === 'error') icon = '✖';

        toast.innerHTML = `
            <span>${icon}</span>
            <span>${message}</span>
        `;

        selectors.toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s reverse forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', init);

})();
