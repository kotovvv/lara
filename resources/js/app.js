import Vue from 'vue';
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';
import "material-design-icons-iconfont/dist/material-design-icons.css";
import '@mdi/font/css/materialdesignicons.css';
import DatetimePicker from "vuetify-datetime-picker";
import './axios/interceptor'; // Подключаем глобальный interceptor

import LoginComponent from './components/loginComponent.vue';
import MainComponent from './Main.vue';

Vue.use(Vuetify);
Vue.use(DatetimePicker);
const app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    theme: {
      themes: {
        light: {
          primary: "#7620df",
          secondary: "#696969",
          accent: "#8c9eff",
          error: "#b71c1c",
        },
        dark: {
          primary: "#7620df",
        },
      },
    },
    icons: {
      iconfont: 'mdi', // default - only for display purposes
    },
  }),
  components: {
    'login-component': LoginComponent,
    'main-component': MainComponent,
  },
});
