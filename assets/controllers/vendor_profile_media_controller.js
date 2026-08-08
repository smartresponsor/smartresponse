import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['coverPreview', 'avatarPreview', 'status', 'manager', 'managerTitle', 'uppy', 'uploadPanel', 'libraryPanel', 'uploadTab', 'libraryTab', 'library'];
    static values = {
        endpoint: { type: String, default: '/attachment/upload' },
        ownerId: String,
    };

    connect() {
        this.slot = null;
        this.uppy = null;
        this.ensureUppyStylesheet();
    }

    disconnect() {
        this.uppy?.destroy?.();
        this.uppy = null;
    }

    openCoverManager() {
        return this.openManager('cover');
    }

    openAvatarManager() {
        return this.openManager('avatar');
    }

    async openManager(slot) {
        this.slot = slot;
        this.managerTitleTarget.textContent = slot === 'cover' ? 'Manage cover image' : 'Manage avatar';
        this.showUpload();
        this.managerTarget.showModal();
        await this.mountUppy(slot);
    }

    closeManager() {
        this.managerTarget.close();
    }

    showUpload() {
        this.uploadPanelTarget.hidden = false;
        this.libraryPanelTarget.hidden = true;
        this.uploadTabTarget.classList.add('ant-segmented-item-selected');
        this.libraryTabTarget.classList.remove('ant-segmented-item-selected');
    }

    async showLibrary() {
        this.uploadPanelTarget.hidden = true;
        this.libraryPanelTarget.hidden = false;
        this.uploadTabTarget.classList.remove('ant-segmented-item-selected');
        this.libraryTabTarget.classList.add('ant-segmented-item-selected');
        await this.loadLibrary();
    }

    async mountUppy(slot) {
        this.uppy?.destroy?.();
        this.uppy = null;
        this.uppyTarget.replaceChildren();
        this.status('Loading image editor...');

        const { Uppy, Dashboard, ImageEditor, XHRUpload } = await import('https://releases.transloadit.com/uppy/v5.2.1/uppy.min.mjs');
        const aspectRatio = slot === 'avatar' ? 1 : 1640 / 624;
        const note = slot === 'avatar'
            ? 'Square image recommended. Crop, rotate, zoom or flip before upload.'
            : 'Wide image recommended (1640 x 624). Crop, rotate, zoom or flip before upload.';

        this.uppy = new Uppy({
            autoProceed: false,
            restrictions: {
                maxNumberOfFiles: 1,
                allowedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
                maxFileSize: 15 * 1024 * 1024,
            },
            meta: {
                ownerType: 'vendor',
                ownerId: this.ownerIdValue,
                context: 'profile',
                slot,
                isPrimary: 'true',
                title: slot === 'avatar' ? 'Vendor profile avatar' : 'Vendor profile cover',
                altText: slot === 'avatar' ? 'Vendor profile avatar' : 'Vendor profile cover image',
            },
        });

        this.uppy.use(Dashboard, {
            target: this.uppyTarget,
            inline: true,
            height: 460,
            width: '100%',
            autoOpen: 'imageEditor',
            proudlyDisplayPoweredByUppy: false,
            showProgressDetails: true,
            note,
            theme: 'auto',
        });
        this.uppy.use(ImageEditor, {
            target: Dashboard,
            quality: 0.9,
            cropperOptions: {
                aspectRatio,
                viewMode: 1,
                background: false,
                autoCropArea: 1,
            },
        });
        this.uppy.use(XHRUpload, {
            endpoint: this.endpointValue,
            method: 'POST',
            formData: true,
            fieldName: 'file',
            allowedMetaFields: ['ownerType', 'ownerId', 'context', 'slot', 'isPrimary', 'title', 'altText'],
            withCredentials: true,
            getResponseData: (xhr) => {
                const payload = JSON.parse(xhr.responseText || '{}');
                return { ...payload, url: payload.downloadUrl || null };
            },
        });

        this.uppy.on('upload-success', (_file, response) => {
            const url = response?.body?.downloadUrl || response?.uploadURL;
            if (url) {
                this.preview(slot === 'cover' ? this.coverPreviewTarget : this.avatarPreviewTarget, url, slot === 'cover');
            }
            this.status(`${slot === 'cover' ? 'Cover' : 'Avatar'} updated.`);
            this.loadLibrary();
        });
        this.uppy.on('upload-error', (_file, error) => {
            this.status(error?.message || 'Upload failed.', true);
        });
        this.status('');
    }

    ensureUppyStylesheet() {
        if (document.querySelector('link[data-uppy-styles]')) return;
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://releases.transloadit.com/uppy/v5.2.1/uppy.min.css';
        link.dataset.uppyStyles = 'true';
        document.head.appendChild(link);
    }

    async loadLibrary() {
        this.libraryTarget.innerHTML = '<div class="ant-skeleton">Loading media library…</div>';
        try {
            const query = new URLSearchParams({
                ownerType: 'vendor',
                ownerId: this.ownerIdValue,
                context: 'profile',
                slot: this.slot,
            });
            const response = await fetch(`/attachment?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
            const images = (payload.items || []).filter((item) => item.mimeType?.startsWith('image/') && item.downloadUrl);
            if (images.length === 0) {
                this.libraryTarget.innerHTML = '<div class="ant-empty"><div class="ant-empty-description">No images uploaded yet.</div></div>';
                return;
            }
            this.libraryTarget.replaceChildren(...images.map((item) => this.libraryCard(item)));
        } catch (error) {
            this.libraryTarget.innerHTML = `<div class="ant-alert ant-alert-error">${this.escapeHtml(error instanceof Error ? error.message : 'Unable to load media library.')}</div>`;
        }
    }

    libraryCard(item) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'interfacing-vendor-media-library__item ant-card ant-card-hoverable';
        button.innerHTML = `<img src="${this.escapeHtml(item.downloadUrl)}" alt="${this.escapeHtml(item.altText || item.originalName || 'Attachment')}" loading="lazy"><span>${this.escapeHtml(item.title || item.originalName || `Attachment ${item.id}`)}</span>`;
        button.addEventListener('click', () => this.selectExisting(item));
        return button;
    }

    async selectExisting(item) {
        const form = new FormData();
        form.append('attachmentId', String(item.id));
        form.append('ownerType', 'vendor');
        form.append('ownerId', this.ownerIdValue);
        form.append('context', 'profile');
        form.append('slot', this.slot);
        form.append('isPrimary', 'true');
        this.busy(true);
        try {
            const response = await fetch('/attachment/attach', { method: 'POST', body: form, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
            this.preview(this.slot === 'cover' ? this.coverPreviewTarget : this.avatarPreviewTarget, item.downloadUrl, this.slot === 'cover');
            this.status(`${this.slot === 'cover' ? 'Cover' : 'Avatar'} selected from media library.`);
            this.closeManager();
        } catch (error) {
            this.status(error instanceof Error ? error.message : 'Unable to select attachment.', true);
        } finally {
            this.busy(false);
        }
    }

    escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
    }

    preview(target, url, background) {
        if (background) {
            target.style.backgroundImage = `linear-gradient(rgba(15,23,42,.18), rgba(15,23,42,.18)), url("${url}")`;
            target.style.backgroundSize = 'cover';
            target.style.backgroundPosition = 'center';
            return;
        }
        let image = target.querySelector('img');
        if (!image) {
            image = document.createElement('img');
            image.className = 'interfacing-vendor-profile-avatar__image';
            target.replaceChildren(image);
        }
        image.src = url;
    }

    busy(value) {
        this.element.toggleAttribute('aria-busy', value);
        this.element.querySelectorAll('[data-vendor-profile-media-action]').forEach((button) => button.disabled = value);
    }

    status(message, error = false) {
        if (!this.hasStatusTarget) {
            return;
        }

        this.statusTarget.textContent = message;
        this.statusTarget.dataset.state = error ? 'error' : 'success';
    }
}
