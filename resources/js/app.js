import Vue from 'vue';
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';
import "material-design-icons-iconfont/dist/material-design-icons.css";
import '@mdi/font/css/materialdesignicons.css';
import DatetimePicker from "vuetify-datetime-picker";
// import 'vuetify-datetime-picker/src/stylus/main.styl';

import LoginComponent from './components/loginComponent.vue';
import MainComponent from './Main.vue';

Vue.use(Vuetify);
Vue.use(DatetimePicker);
const app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    icons: {
      iconfont: 'mdi', // default - only for display purposes
    },
  }),
  components: {
    'login-component': LoginComponent,
    'main-component': MainComponent,
  },
});
