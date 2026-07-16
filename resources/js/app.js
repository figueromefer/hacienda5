import './bootstrap';

import Alpine from 'alpinejs';
import moneyInput from './money-input';

window.Alpine = Alpine;

Alpine.data('moneyInput', moneyInput);

Alpine.start();
