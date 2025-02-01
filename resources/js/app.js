import Vue from 'vue';
import Vuetify from 'vuetify';
import 'vuetify/dist/vuetify.min.css';
import '@mdi/font/css/materialdesignicons.css';
import LoginComponent from './components/loginComponent.vue';
import MainComponent from './Main.vue';

Vue.use(Vuetify);

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
