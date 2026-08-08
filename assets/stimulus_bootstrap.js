import { startStimulusApp } from '@symfony/stimulus-bundle';
import VendorProfileMediaController from './controllers/vendor_profile_media_controller.js';

const app = startStimulusApp();
app.register('vendor-profile-media', VendorProfileMediaController);
