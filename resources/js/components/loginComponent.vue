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
                  <v-form>
                    <v-text-field
                      label="Логин"
                      name="name"
                      type="text"
                      v-model="fields.name"
                    >
                      <v-icon slot="prepend" color="blue">
                        mdi-account-outline
                      </v-icon>
                    </v-text-field>

                    <v-text-field
                      id="password"
                      label="Пароль"
                      name="password"
                      type="password"
                      v-model="fields.password"
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
export default {
  props: ["login"],
  data: () => ({
    drawer: null,
    options: {
      isLoggingIn: true,
      shouldStayLoggedIn: true,
    },
    fields: {
      name: "lara",
      password: "lara",
    },
    errors: {},
  }),
  methods: {
    onSubmit() {
      this.errors = {};
      axios
        .post("/api/login", this.fields)
        .then((response) => {
          this.$emit("login", response.data.user);
        })
        .catch((error) => {
          if (error.response.status === 422) {
            this.errors = error.response.data.errors || {};
          }
        });
    },
  },
};
</script>

<style scoped>
</style>
