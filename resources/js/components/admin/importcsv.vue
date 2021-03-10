<template>
  <v-row>
    <v-col cols="3">
      <v-select
        :items="providers"
        label="Поставщик"
        item-text="name"
        item-value="id"
      ></v-select>
    </v-col>
    <v-col cols="3">
      <v-file-input
        v-model="files"
        ref="fileupload"
        label="Загрузите CSV"
        show-size
        truncate-length="24"
        @change="onFileChange"
      ></v-file-input>
    </v-col>
  </v-row>
</template>

<script>
import axios from "axios";
export default {
  data: () => ({
    providers: [],
    files: [],
  }),
  mounted() {
    this.getProviders();
  },
  methods: {
    getProviders() {
      axios
        .get("/api/provider")
        .then((res) => {
          this.providers = res.data.map(({ name, id }) => ({ name, id }));
        })
        .catch((error) => console.log(error));
    },
    onFileChange(f) {
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      } else {
        this.files = [];
      }
    },
    createInput(file) {
      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        var vm = this;
        reader.onload = (e) => {
          resolve((vm.fileinput = reader.result));
        };
        reader.readAsText(file);
      });

      promise.then(
        (result) => {
          /* handle a successful result */
          console.log(this.fileinput);
        },
        (error) => {
          /* handle an error */
          console.log(error);
        }
      );
    },
  },
};
</script>
