import { startStimulusApp } from '@symfony/stimulus-bundle';
import MenuController from './controllers/menu_controller.js';
import ProductDetailController from './controllers/product_detail_controller.js';
import ProductImageController from './controllers/product_image_controller.js';
import RentalPeriodController from './controllers/rental_period_controller.js';
import TestimonialsController from './controllers/testimonials_controller.js';
import CartController from './controllers/cart_controller.js';

const app = startStimulusApp();
app.register('menu', MenuController);
app.register('product-detail', ProductDetailController);
app.register('product-image', ProductImageController);
app.register('rental-period', RentalPeriodController);
app.register('testimonials', TestimonialsController);
app.register('cart', CartController);
