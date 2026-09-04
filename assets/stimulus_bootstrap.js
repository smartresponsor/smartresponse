import { startStimulusApp } from '@symfony/stimulus-bundle';
import CartController from './controllers/cart_controller.js';
import VendorProfileMediaController from './controllers/vendor_profile_media_controller.js';

const app = startStimulusApp();
app.register('cart', CartController);
app.register('vendor-profile-media', VendorProfileMediaController);
