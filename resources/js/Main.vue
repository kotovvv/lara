<template>
  <div>
    <component
      ref="main"
      :user="user"
      v-on:login="onLogin"
      v-on:logout="onLogout"
      :is="theComponent"
    />
  </div>
</template>

<script>
const logincomponent = () => import("./components/loginComponent.vue");
const admincomponent = () => import("./components/admin/adminComponent.vue");
const crmcomponent = () => import("./components/crmanager/crmComponent.vue");
const managercomponent = () =>
  import("./components/manager/managerComponent.vue");
const providercomponent = () =>
  import("./components/provider/providerComponent.vue");
import axios from "axios";
import Cookies from "js-cookie";

export default {
  data: () => ({
    theMenu: "login",
    user: {},
  }),
  computed: {
    theComponent() {
      if (!this.user.role_id) return logincomponent;
      if (this.user.role_id == 1) return admincomponent;
      if (this.user.role_id == 2) return crmcomponent;
      if (this.user.role_id == 3) return managercomponent;
      if (this.user.role_id == 4) return providercomponent;
    },
  },
  methods: {
    onLogin(data) {
      if (data.token == "") return;
      this.user = data;
      const secure = window.location.protocol === "https:";
      Cookies.set("auth_token", data.token, {
        sameSite: "None",
        secure: secure,
      });
      window.location.href = "/";
    },
    onLogout() {
      this.clear();
      // No need to redirect to /login, the component will handle it
    },
    clear() {
      this.user = {};
      Cookies.remove("auth_token");
      //Cookies.remove("XSRF-TOKEN"); // Ensure XSRF-TOKEN is removed
    },
    async fetchUser() {
      const token = Cookies.get("auth_token");
      if (token == "") return;
      if (token) {
        try {
          const response = await axios.get("/api/user", {
            headers: { Authorization: `Bearer ${token}` },
          });
          this.user = response.data;
        } catch (error) {
          console.error("Error fetching user:", error);
          this.clear();
        }
      }
    },
  },
  mounted: function () {
    this.fetchUser();
  },
};
</script>
