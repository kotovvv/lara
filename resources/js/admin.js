import Vue from 'vue'
import vuetify from './vuetify';

Vue.config.productionTip = false;


Vue.component('admin-component', require('./components/admin/main.vue').default);
new Vue({
    vuetify,
  }).$mount('#app')