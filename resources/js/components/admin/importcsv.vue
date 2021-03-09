<template>
  <v-row>
    <v-col cols="4">
      <v-select :items="provider" label="Поставщик"></v-select>
    </v-col>
    <v-col cols="4">
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
export default {
  data: () => ({
    provider: ['Провайдер 1','Провайдер 2','Провайдер 3','Провайдер 4'],
    files: [],
  }),
  methods: {
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
