import Vue from 'vue'
import vuetify from './vuetify';
Vue.component('manager-component', require('./components/manager/main.vue').default);
new Vue({
    vuetify,
}).$mount('#app')
