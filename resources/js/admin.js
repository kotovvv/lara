import Vue from 'vue'
import vuetify from './vuetify';

import MultiFiltersPlugin from '../plugins/MultiFilters';

Vue.config.productionTip = false;

//Vue.use(MultiFiltersPlugin);

Vue.component('admin-component', require('./components/admin/main.vue').default);
new Vue({
    vuetify,
  }).$mount('#app')