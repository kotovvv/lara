<template>
  <div>
    <component ref="main" :user="user" v-on:login="onLogin" :is="theComponent" />
  </div>
</template>

<script>
const logincomponent = () => import("./components/loginComponent");
const admincomponent = () => import("./components/admin/adminComponent");
const crmcomponent = () => import("./components/crmanager/crmComponent");
const managercomponent = () => import("./components/manager/managerComponent");
export default {
  //  name:'main',
  data: () => ({
    theMenu: "login",
    user: {},
  }),
  computed: {
    theComponent() {
      if (this.user.role_id == undefined) return logincomponent;
      if (this.user.role_id == 1) return admincomponent;
      if (this.user.role_id == 2) return crmcomponent;
      if (this.user.role_id == 3) return managercomponent;
    },
  },
  methods: {
onLogin (data) {
  this.user = data
  if(this.user.role_id == undefined) localStorage.clear()
  },
  isExist(user) {
    return (!!localStorage[user])
}
  },
mounted: function () {
  if (this.isExist('user')) this.user = JSON.parse(localStorage.user)
}
};
</script>