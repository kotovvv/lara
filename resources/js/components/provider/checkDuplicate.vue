<template>
  <v-container>
    <v-snackbar v-model="snackbar" top right timeout="-1">
      {{ message }}
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>


    <v-row>
      <v-col cols="3">
        <p>
          <v-radio-group v-model="email_tel" row>
            <v-radio label="email" value="email"></v-radio>
            <v-radio label="телефон" value="tel"></v-radio>
          </v-radio-group>
        </p>
        <v-textarea class="border pa-3" v-model="list_email" label="Почтовые адреса или телефоны "></v-textarea>
      </v-col>
      <v-col cols="2">
        <v-file-input v-model="file_emails" label="загрузить txt" show-size truncate-length="24"
          @change="onFileChange"></v-file-input>
        <v-btn @click="checkEmails" v-if="list_email" class="primary">Проверить<v-progress-circular v-if="loading"
            indeterminate color="amber"></v-progress-circular></v-btn>
      </v-col>
      <v-col cols="3">

        <p>
          <b>Всего {{ message_all }}</b>
        </p>
        <div v-if="in_db.length" class="mt-4">
          <v-btn @click="download('in')">{{
            "Скачать дубликаты (" + in_db.length + ")"
          }}</v-btn>
        </div>
        <div v-if="out_db.length" class="mt-4">
          <v-btn @click="download('out')">{{
            "Скачать уникальные (" + out_db.length + ")"
          }}</v-btn>
        </div>
      </v-col>
      <v-col cols="12"
        v-if="(dup.length || dup_all.length) && ($props.user.role_id == 1 || ($props.user.role_id == 4 && $props.user.showInfo == 1))">
        <div class="wrp__statuses">
          <template v-for="(i, x) in d_statuses">
            <div class="status_wrp" :class="{
              borderactive: filter_status.includes(i.id),
              bordernot: !filter_status.includes(i.id),
            }" :key="x" @click="changeFilterStatus(i.id)">
              <b :style="{
                background: i.color,
                outline: '1px solid' + i.color,
              }">{{ i.hm }}</b>
              <span>{{ i.name }}</span>
              <v-btn v-if="filter_status.includes(i.id)" icon x-small>
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </div>
          </template>
        </div>
      </v-col>
    </v-row>

    <v-row
      v-if="(dup.length || dup_all.length) && ($props.user.role_id == 1 || ($props.user.role_id == 4 && $props.user.showInfo == 1))">
      <v-col cols="12">
        <!-- <v-tabs v-model="tabs">
          <v-tab>За {{ hmmonth }} мес.</v-tab>
          <v-tab>Всего</v-tab>
        </v-tabs> -->
        <v-col cols="12">
          <v-data-table :headers="duplicate_leads_headers" item-key="id" :items="filtereduplicate_leads"
            id="duplicate_leads" ref="duplicatetable" :footer-props="{
              'items-per-page-options': [100, 200, 500, -1],
              'items-per-page-text': '',
            }">
            <template v-slot:expanded-item="{ headers, item }">
              <td :colspan="headers.length" class="blackborder" v-if="
                $props.user.office_id == 0 ||
                item.office_id == $props.user.office_id
              ">
                <logtel :lid_id="item.id" :key="item.id" />
              </td>
            </template>
            <template v-slot:item.name="{ item }">
              <div class="d-flex">
                {{ item.name }}
                <v-btn icon x-small @click.stop="copyToClickboard(item, 'name')"
                  title="Скопировать имя, емаил, телефон, поставщик ">
                  <v-icon>mdi-content-copy</v-icon>
                </v-btn>
              </div>
            </template>
            <template v-slot:item.email="{ item }">
              <template>
                <div class="d-flex">
                  {{ item.email }}
                  <v-btn icon x-small @click.stop="copyToClickboard(item, 'email')" title="Скопировать email">
                    <v-icon>mdi-content-copy</v-icon>
                  </v-btn>
                </div>
              </template>

            </template>

            <template v-slot:item.tel="{ item }">
              <template>
                <div class="d-flex">
                  <span>{{ item.tel }}</span>
                  <v-btn icon x-small @click.stop="copyToClickboard(item, 'tel')" title="Скопировать телефон">
                    <v-icon>mdi-content-copy</v-icon>
                  </v-btn>
                </div>
              </template>

            </template>
            <template v-slot:item.status_name="{ item }">
              <div class="d-flex">
                <span>{{ item.status_name }}</span>
                <v-btn icon x-small @click.stop="copyToClickboard(item, 'status_name')"
                  title="Скопировать статус, сообщение ">
                  <v-icon>mdi-content-copy</v-icon>
                </v-btn>
              </div>
            </template>
            <template v-slot:top="{ pagination, options, updateOptions }">
              <v-row>
                <v-col>
                  <v-data-footer :pagination="pagination" :options="options" @update:options="updateOptions"
                    :items-per-page-options="pages" items-per-page-text="" />
                </v-col>
              </v-row>
            </template>
          </v-data-table>
        </v-col>
      </v-col>
    </v-row>
  </v-container>
</template>



<script>
import axios from "axios";
import XLSX from "xlsx";
import _ from "lodash";
import logtel from "../manager/logtel";
export default {
  name: "CheckDuplicate",
  props: {
    user: {
      type: Object,
      default: () => ({}),
    },
  },
  components: { logtel },
  data() {
    return {
      statuses: [],
      providers: [],
      offices: [],
      filterOffices: [],
      email_tel: "email",
      hmmonth: 6,
      list_email: "",
      file_emails: [],
      dup: [],
      dup_all: [],
      in_db: [],
      out_db: [],
      in_db_all: [],
      out_db_all: [],
      message: "",
      message_all: "",
      snackbar: false,
      loading: false,
      activeRequests: 0,

      filter_status: [],
      filter_provider: [],
      filter_office: [],
      expanded: [],

      duplicate_leads_headers: [
        { text: "Имя", value: "name" },
        { text: "Email", value: "email" },
        { text: "Телефон.", align: "start", value: "tel" },
        // { text: "Поставщик", value: "provider_name" },
        // { text: "Оффис", value: "office_name" },
        // { text: "Менеджер", value: "user_name" },
        { text: "Создан", value: "date_created" },
        { text: "Изменён", value: "date_updated" },
        { text: "Статус", value: "status_name" },
        // { text: "Депозит", value: "deposit" },
        // { text: "Сообщение", value: "text" },
        // { text: "Звонков", value: "qtytel" },
        // { text: "ПЕРЕЗВОН", value: "ontime" },
        // { text: "Pending", value: "pending" },
        // { text: "Депозит", value: "depozit" },
      ],
      pages: [100, 200, 500, -1],
      tabs: 1,
    };
  },
  mounted() {
    this.getStatuses();
  },
  computed: {
    filtereduplicate_leads() {
      // Выбираем массив данных в зависимости от выбранной вкладки
      // const leads = this.tabs === 1 ? this.dup_all : this.dup;
      const leads = this.dup_all;
      return leads.filter((i) => {
        return (
          (this.filter_status.length == 0 ||
            this.filter_status.includes(i.status_id))
        );
      });
    },
    d_statuses() {
      // Универсальный расчёт статусов для текущего массива данных
      const leads = this.tabs === 1 ? this.dup_all : this.dup;
      const a_status = _.uniq(leads.map((el) => el.status_id));
      const a_hm = _.groupBy(leads, "status_id");
      return (this.statuses || [])
        .filter((i) => a_status.includes(i.id))
        .map((el) => ({
          ...el,
          hm: a_hm[el.id] ? a_hm[el.id].length : 0,
        }));
    },

  },
  methods: {
    download(inout) {
      let filename = inout == "in" ? "duplicat" : "unique" + "_db.txt";
      let text = "";
      if (inout == "in") {
        text = this.in_db.toString().replace(/[,]/, "\n");
      } else {
        text = this.out_db.toString().replace(/[,]/, "\n");
      }

      let element = document.createElement("a");
      element.setAttribute(
        "href",
        "data:text/plain;charset=utf-8," + encodeURIComponent(text)
      );
      element.setAttribute("download", filename);

      element.style.display = "none";
      document.body.appendChild(element);

      element.click();
      document.body.removeChild(element);
    },
    copyToClickboard(item, type) {
      if (type == "name") {
        navigator.clipboard.writeText(
          item[type] +
          ", " +
          item.email +
          ", " +
          item.tel +
          ", " +
          item.provider
        );
      } else if (type == "status_name") {
        navigator.clipboard.writeText(item[type] + ": " + item.text);
      } else {
        navigator.clipboard.writeText(item[type]);
      }
      this.snackbar = true;
      this.message = "Cкопировано в буфер обмена";
    },
    // Check if a row is unmasked (unmasked means the phone/email is shown in full)
    isRowUnmasked(rowId) {
      return this.unmaskedRowId === rowId;
    },
    // Set a row as unmasked
    setRowUnmasked(rowId) {
      this.unmaskedRowId = rowId;
    },
    // Clear the unmasked row
    clearUnmaskedRow() {
      this.unmaskedRowId = null;
    },

    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ uname, name, id, color, order }) => ({
            uname,
            name,
            id,
            color,
            order,
          }));
        })
        .catch((error) => console.log(error));
    },
    filter(evt) {
      evt = evt ? evt : window.event;
      let expect = evt.target.value.toString() + evt.key.toString();
      if (!/^[-+]?[0-9]*\.?[0-9]*$/.test(expect)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
    changeTabImport(tab) {
      this.tabs = tab;
      this.filter_status = [];

    },

    changeFilterStatus(status_id) {
      if (this.filter_status.includes(status_id)) {
        const index = this.filter_status.indexOf(status_id);
        this.filter_status.splice(index, 1);
      } else {
        this.filter_status.push(status_id);
      }
    },
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
        this.file_emails = [];
      }
    },
    txt2Array(txt) {
      this.list_email = txt;
    },
    createInput(file) {
      let ext = file.name.slice(
        (Math.max(0, file.name.lastIndexOf(".")) || Infinity) + 1
      );
      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        reader.onload = (e) => {
          resolve((this.fileinput = reader.result));
        };
        if (ext == "txt" || ext == "csv") {
          reader.readAsText(file);
        } else {
          reader.readAsBinaryString(file);
        }
      });
      promise.then(
        (result) => {
          if (ext == "txt" || ext == "csv") {
            this.txt2Array(this.fileinput);
          } else {
            var workbook = XLSX.read(result, { type: "binary" }),
              firstSheetName = workbook.SheetNames[0],
              worksheet = workbook.Sheets[firstSheetName];
            this.list_email = XLSX.utils.sheet_to_csv(worksheet, {
              header: 1,
            });
          }
        },
        (error) => {
          console.log(error);
        }
      );
    },
    checkEmails() {
      let vm = this;
      vm.activeRequests++;
      vm.loading = true;
      vm.snackbar = false;
      vm.message = "";
      vm.message_all = "";
      vm.dup = [];
      vm.dup_all = [];
      vm.in_db = [];
      vm.out_db = [];
      vm.in_db_all = [];
      vm.out_db_all = [];
      let data = {};
      data.emails = vm.list_email.replace(/[\r\t ]/g, "").split("\n");
      data.check = 1;
      data.email_tel = vm.email_tel;
      data.hmmonth = vm.hmmonth;
      axios
        .post("api/checkEmails", data)
        .then(function (res) {
          vm.in_db = res.data.emails.filter((n) => n);
          vm.out_db = [
            ...new Set(
              data.emails.filter((i) => !vm.in_db.includes(i.toLowerCase()))
            ),
          ];
          vm.out_db = vm.out_db.filter((e) => e != "");
          vm.message =
            "Уникальных: " +
            vm.out_db.length +
            " Дубликатов: " +
            vm.in_db.length;
          vm.in_db_all = res.data.emails_all.filter((n) => n);
          vm.out_db_all = [
            ...new Set(
              data.emails.filter((i) => !vm.in_db_all.includes(i.toLowerCase()))
            ),
          ];
          vm.out_db_all = vm.out_db_all.filter((e) => e != "");
          vm.message =
            "Уникальных: " +
            vm.out_db.length +
            " Дубликатов: " +
            vm.in_db.length;
          vm.message_all =
            "Уникальных: " +
            vm.out_db_all.length +
            " Дубликатов: " +
            vm.in_db_all.length;
          vm.snackbar = true;
          vm.dup = res.data.leads;
          vm.dup = vm.dup.map((dl) => {
            dl.date_created = dl.created_at.substring(0, 10);
            dl.date_updated = dl.updated_at.substring(0, 10);
            return dl;
          });
          vm.dup_all = res.data.leads_all;
          vm.dup_all = vm.dup_all.map((dl) => {
            dl.date_created = dl.created_at.substring(0, 10);
            dl.date_updated = dl.updated_at.substring(0, 10);
            return dl;
          });
          console.log("dup_all", vm.dup_all);
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    exportXlsx() {
      const self = this;
      let unique = [];
      var wb = XLSX.utils.book_new();
      var wbuni = XLSX.utils.book_new();
      // Выбираем уникальные значения в зависимости от выбранной вкладки
      const outDb = self.tabs === 1 ? self.out_db_all : self.out_db;
      if (self.email_tel === "tel") {
        unique = outDb.map((i) => ({
          id: "",
          created: "",
          updated: "",
          status_id: "",
          status_name: "",
          name: "name" + i,
          email: i + "@unique.com",
          tel: i,
        }));
      } else {
        unique = outDb.map((i) => ({ email: i }));
      }
      unique = unique.filter((el) => {
        return ![9, 10, 20].includes(el["status_id"]);
      });
      let con = [];
      let a_bad_tel = [];
      self.filtereduplicate_leads.map((t) => {
        if ([9, 10, 11, 20, 21, 22, 23].includes(t.status_id))
          a_bad_tel.push(t["tel"]);
      });
      const dup_not = self.filtereduplicate_leads.filter((dd) => {
        return !a_bad_tel.includes(dd["tel"]);
      });
      if (
        self.$props.user &&
        self.$props.user.role_id == 1 &&
        self.$props.user.office_id == 0
      ) {
        con = con.concat(unique, dup_not);
        window["con"] = XLSX.utils.json_to_sheet(con);
        XLSX.utils.book_append_sheet(wb, window["con"], "CHECK_TO_UPLOAD");
      }
      window["unique"] = XLSX.utils.json_to_sheet(unique);
      XLSX.utils.book_append_sheet(wb, window["unique"], "unique");
      XLSX.utils.book_append_sheet(wbuni, window["unique"], "unique");
      if (
        self.$props.user &&
        self.$props.user.role_id == 1 &&
        self.$props.user.office_id == 0
      ) {
        window["list"] = XLSX.utils.json_to_sheet(self.filtereduplicate_leads);
      } else {
        let duplicate = [];
        if (self.email_tel === "tel") {
          duplicate = self.filtereduplicate_leads.map((i) => {
            return {
              tel: i.tel,
            };
          });
        } else {
          duplicate = self.filtereduplicate_leads.map((i) => {
            return {
              email: i.email,
            };
          });
        }
        window["list"] = XLSX.utils.json_to_sheet(duplicate);
      }
      XLSX.utils.book_append_sheet(wb, window["list"], "duplicate");
      if (
        self.$props.user &&
        self.$props.user.role_id == 1 &&
        self.$props.user.office_id == 0
      ) {
        const dup_dep = self.filtereduplicate_leads.filter((dd) => {
          return dd.status_id == 10;
        });
        window["dup_dep"] = XLSX.utils.json_to_sheet(dup_dep);
        XLSX.utils.book_append_sheet(
          wb,
          window["dup_dep"],
          "duplicate_doposit"
        );
        const dup_cb = self.filtereduplicate_leads.filter((dd) => {
          return dd.status_id == 9;
        });
        window["dup_cb"] = XLSX.utils.json_to_sheet(dup_cb);
        XLSX.utils.book_append_sheet(
          wb,
          window["dup_cb"],
          "duplicate_callback"
        );
        const dup_3week = self.filtereduplicate_leads.filter((dd) => {
          return (
            [9, 10, 11, 23].includes(dd.status_id) &&
            (Date.now() - Date.parse(dd.created)) / (60 * 60 * 24 * 1000) < 21
          );
        });
        window["dup_3week"] = XLSX.utils.json_to_sheet(dup_3week);
        XLSX.utils.book_append_sheet(
          wb,
          window["dup_3week"],
          "duplicate_3week"
        );
      }
      XLSX.writeFile(
        wb,
        unique.length +
        "-un_" +
        self.in_db.length +
        "-dup" +
        "_" +
        new Date().toDateString() +
        ".xlsx"
      );
      if (unique.length) {
        XLSX.writeFile(
          wbuni,
          unique.length + "-un_" + new Date().toDateString() + ".xlsx"
        );
      }
    },
    clickrowe(item, row) {
      if (!row.isExpanded) {
        this.expanded = [item];
      } else {
        this.expanded = [];
      }
    },
  },
};
</script>

<style>
.borderactive {
  border: 4px solid #47cf0b;
}

.bordernot {
  border: 4px solid transparent;
}

.radiolabel label {
  margin-bottom: 0;
}
</style>
}
.bordernot {
border: 4px solid transparent;
}
.radiolabel label {
margin-bottom: 0;
}
</style>
