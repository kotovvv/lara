import Vue from "vue";
import Vuetify from "vuetify";
import "vuetify/dist/vuetify.min.css";
Vue.use(Vuetify);

export default new Vuetify({
  theme: {
    themes: {
      light: {
        primary: "#9C27B0",
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
    iconfont: "mdi", // 'mdi' || 'mdiSvg' || 'md' || 'fa' || 'fa4' || 'faSvg'
  },
});
