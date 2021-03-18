import Vue from 'vue'
import vuetify from './vuetify';
import DatetimePicker from 'vuetify-datetime-picker'
Vue.component('manager-component', require('./components/manager/main.vue').default);
// Vue.component('datetime-picker'), DatetimePicker
Vue.use(DatetimePicker);

new Vue({
    vuetify,

}).$mount('#app')
