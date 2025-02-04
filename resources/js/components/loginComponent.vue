<template>
  <v-app id="inspire">
    <v-main>
      <v-container fluid fill-height>
        <v-layout align-center justify-center>
          <v-flex xs12 sm8 md4>
            <v-card class="elevation-12">
              <v-toolbar color="primary" dark flat>
                <v-toolbar-title>Подключиться к системе</v-toolbar-title>
              </v-toolbar>
              <form>
                <v-card-text>
                  <v-form ref="form">
                    <v-text-field
                      label="Логин"
                      name="name"
                      type="text"
                      v-model="fields.name"
                      :rules="userNameRequired"
                      required
                      @keyup.enter.native="onSubmit"
                    >
                      <v-icon slot="prepend" color="blue">
                        mdi-account-outline
                      </v-icon>
                    </v-text-field>

                    <v-text-field
                      id="password"
                      label="Пароль"
                      name="password"
                      :type="showPassword ? 'text' : 'password'"
                      v-model="fields.password"
                      :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                      @click:append="showPassword = !showPassword"
                      :rules="passwordRequired"
                      required
                      @keyup.enter.native="onSubmit"
                    >
                      <v-icon slot="prepend" color="blue">
                        mdi-textbox-password
                      </v-icon>
                    </v-text-field>
                  </v-form>
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn color="primary" @click="onSubmit">Войти</v-btn>
                </v-card-actions>
              </form>
            </v-card>
          </v-flex>
        </v-layout>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import axios from "axios";
import Cookies from "js-cookie";

export default {
  props: ["login"],
  data: () => ({
    drawer: null,
    options: {
      isLoggingIn: true,
      shouldStayLoggedIn: true,
    },
    fields: {
      name: "",
      password: "",
    },
    errors: {},
    showPassword: false,
    userNameRequired: [(v) => !!v || "без логина?"],
    passwordRequired: [(v) => !!v || "А Пароль?"],
  }),
  methods: {
    onSubmit() {
      if (this.fields.name === "" || this.fields.password === "") {
        this.errors = {
          name: this.fields.name === "" ? ["без логина?"] : [],
          password: this.fields.password === "" ? ["А Пароль?"] : [],
        };
        return;
      }
      const self = this;
      localStorage.removeItem("user");
      this.errors = {};
      axios
        .post("/api/login", this.fields)
        .then((response) => {
          if (response.data.token == "") return;
          self.$emit("login", response.data.user);
          const secure = window.location.protocol === "https:";
          Cookies.set("auth_token", response.data.token, {
            sameSite: "None",
            secure: secure,
          });
          window.location.href = "/";
        })
        .catch((error) => {
          if (error.response.status === 422) {
            self.errors = error.response.data.errors || {};
          }
        });
    },
    clear() {
      this.user = {};
      Cookies.remove("auth_token");
      //Cookies.remove("XSRF-TOKEN"); // Ensure XSRF-TOKEN is removed
    },
  },
};
</script>

<style scoped>
</style>
