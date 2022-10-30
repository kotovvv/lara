<template>
  <div>
    <v-conteiner>
      <v-row>
        <v-col cols="3">
          <v-file-input
            v-model="files"
            ref="fileupload"
            label="загрузить файл ВТС CSV"
            show-size
            truncate-length="24"
            @change="onFileChange"
          ></v-file-input>
        </v-col>
      </v-row>
      <v-row>
        <v-col>
          <v-row class="px-4">
            <v-col><p>С Дата</p></v-col>
            <v-col><p>По Дата</p></v-col>
          </v-row>

          <div class="status_wrp wrp_date px-3">
            <v-row align="center">
              <v-col>
                <v-menu
                  v-model="dateFrom"
                  :close-on-content-click="false"
                  :nudge-right="40"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="datetimeFrom"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    locale="ru-ru"
                    v-model="datetimeFrom"
                    @input="
                      dateFrom = false;
                      savedates == true ? getBTCsOnDate() : null;
                    "
                  ></v-date-picker>
                </v-menu>
              </v-col>
              <v-col>
                <v-menu
                  v-model="dateTo"
                  :close-on-content-click="false"
                  :nudge-right="40"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="datetimeTo"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    locale="ru-ru"
                    v-model="datetimeTo"
                    @input="
                      dateTo = false;
                      getBTCsOnDate();
                    "
                  ></v-date-picker>
                </v-menu>
              </v-col>
            </v-row>
          </div>
        </v-col>
        <v-col>
          <p>Фильтр office</p>
          <v-select
            v-model="filterOffices"
            :items="offices"
            item-text="name"
            item-value="id"
            outlined
            rounded
            multiple
          >
            <template v-slot:selection="{ item, index }">
              <v-chip v-if="index === 0">
                <span>{{ item.name }}</span>
              </v-chip>
              <span v-if="index === 1" class="grey--text text-caption">
                (+{{ filterOffices.length - 1 }} )
              </span>
            </template>
          </v-select>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12">
          <v-data-table
            id="tablids"
            :headers="headers"
            item-key="id"
            :items="filteredItems"
            ref="datatable"
            :footer-props="{
              'items-per-page-options': [],
              'items-per-page-text': '',
            }"
            :disable-items-per-page="true"
            :loading="loading"
            loading-text="Загружаю... Ожидайте"
          >
          </v-data-table>
        </v-col>
      </v-row>
    </v-conteiner>
  </div>
</template>

<script>
export default {
  data() {
    return {
      files: [],
      message: "",
      loading: false,
      filteredItems: [],
      headers: [{text: "Лид", value:"lead_id"}, {text: "Адрес",value:"address"}, {text: "Сумма",value:"summ"}, {text: "TRX",value:"trx_count"}],
      modal: false,
      dateFrom: false,
      dateTo: false,

      dateProps: { locale: "ru-RU", format: "24hr" },
      datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 14))
        .toISOString()
        .substring(0, 10),
      datetimeTo: new Date().toISOString().substring(0, 10),
    };
  },
  methods: {
    getBTCsOnDate() {},
    onFileChange(f) {
      if (f == null) return;
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
        "text/plain",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      } else {
        this.files = [];
      }
    },
    csvJSON(csv) {
      var vm = this;
      var lines = csv.split("\n");
      var result = [];
      var headers = lines[0].split(";");
      headers = ["name", "email", "tel", "afilyator"];

      lines.map(function (line, indexLine) {
        if (indexLine < 1) return; // Jump header line

        var obj = {};
        line = line.trim();
        var currentline = line.split(";");

        headers.map(function (header, indexHeader) {
          obj[header] = currentline[indexHeader];
        });

        result.push(obj);
      });

      result.pop(); // remove the last item because undefined values

      return result; // JavaScript object
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
          let vm = this;
          vm.parse_csv = vm
            .csvJSON(this.fileinput)
            .filter(
              (v, i, a) =>
                a.findIndex(
                  (t) => t.afilyator + t.tel == v.afilyator + v.tel
                ) === i
            );
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
