<template>
  <v-container fluid>
    <v-row>
      <v-col class="d-flex align-center radiolabel">
        <v-radio-group v-model="email_tel" row>
          <v-radio label="email" value="email"></v-radio>
          <v-radio label="телефон" value="tel"></v-radio>
        </v-radio-group>
        <span class="label">Выгрузка дублей за </span>
        <v-text-field
          style="max-width: 3rem; padding-top: 0"
          class="inline-block center"
          @keypress="filter()"
          v-model.number="hmmonth"
          hide-details="auto"
        ></v-text-field>
        <span class="label"> месяц(ев)</span>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="6">
        <v-textarea
          class="border pa-3"
          v-model="list_email"
          label="Почтовые адреса или телефоны "
        ></v-textarea>
      </v-col>
      <v-col cols="6">
        <v-file-input
          v-model="file_emails"
          label="загрузить txt"
          show-size
          truncate-length="24"
          @change="onFileChange"
        ></v-file-input>
        <v-btn @click="checkEmails" v-if="list_email" class="primary"
          >Проверить<v-progress-circular
            v-if="loading"
            indeterminate
            color="amber"
          ></v-progress-circular
        ></v-btn>
      </v-col>
      <v-col cols="12">
        <div class="wrp__statuses">
          <template v-for="(i, x) in d_statuses">
            <div
              class="status_wrp"
              :class="{
                borderactive: filter_status.includes(i.id),
                bordernot: !filter_status.includes(i.id),
              }"
              :key="x"
              @click="changeFilterStatus(i.id)"
            >
              <b
                :style="{
                  background: i.color,
                  outline: '1px solid' + i.color,
                }"
                >{{ i.hm }}</b
              >
              <span>{{ i.name }}</span>
              <v-btn v-if="filter_status.includes(i.id)" icon x-small>
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </div>
          </template>
        </div>
      </v-col>
      <v-col cols="12" v-if="duplicate_leads.length || out_db.length">
        <v-data-table
          :headers="duplicate_leads_headers"
          item-key="id"
          :items="filtereduplicate_leads"
          id="duplicate_leads"
          ref="duplicatetable"
          @click:row="clickrowe"
          show-expand
          :expanded.sync="expanded"
          :footer-props="{
            'items-per-page-options': [100, 200, 500, -1],
            'items-per-page-text': '',
          }"
          v-model.lazy.trim="selectedTop"
          show-select
        >
          <template v-slot:expanded-item="{ headers, item }">
            <td
              :colspan="headers.length"
              class="blackborder"
              v-if="
                $attrs.user.office_id == 0 ||
                item.office_id == $attrs.user.office_id
              "
            >
              <logtel :lid_id="item.id" :key="item.id" />
            </td>
          </template>

          <template v-slot:item.email="{ item }">
            <template
              v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0"
            >
              {{ item.email }}
            </template>
            <template v-else>
              <MaskedField
                :value="item.email"
                type="email"
                :isUnmasked="isRowUnmasked(item.id)"
              />
            </template>
          </template>
          <template v-slot:item.tel="{ item }">
            <template
              v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0"
            >
              {{ item.tel }}
            </template>
            <template v-else>
              <MaskedField
                :value="item.tel"
                type="phone"
                :isUnmasked="isRowUnmasked(item.id)"
              />
            </template>
          </template>
          <template v-slot:top="{ pagination, options, updateOptions }">
            <v-row>
              <v-col cols="2">
                <v-btn v-if="selectedTop.length" @click="setTop"
                  >Назначить приоритет</v-btn
                ></v-col
              >
              <v-col>
                <!--:menu-props="{ maxHeight: '80vh' }" -->
                <v-autocomplete
                  v-model="filter_provider"
                  label="Фильтр поставщик"
                  :items="d_providers"
                  item-text="name"
                  item-value="id"
                  outlined
                  rounded
                  multiple
                  clearable="clearable"
                >
                  <template v-slot:selection="{ item, index }">
                    <span v-if="index === 0">{{ item.name }} </span>
                    <span v-if="index === 1" class="grey--text text-caption">
                      (+{{ filter_provider.length - 1 }} )
                    </span>
                  </template>
                  <template v-slot:item="{ item, attrs }">
                    <v-badge
                      :value="attrs['aria-selected'] == 'true'"
                      color="#7620df"
                      dot
                      left
                    >
                      {{ item.name }}
                    </v-badge>
                  </template>
                </v-autocomplete></v-col
              >
              <v-col>
                <v-select
                  v-model="filter_office"
                  :items="d_offices"
                  item-text="name"
                  item-value="id"
                  label="Фильтр офис"
                  outlined
                  rounded
                  multiple
                >
                </v-select
              ></v-col>
              <v-col>
                <v-btn outlined rounded @click="exportXlsx" class="border">
                  <v-icon left> mdi-file-excel </v-icon>
                  Скачать таблицу
                </v-btn></v-col
              >
              <v-col>
                <v-data-footer
                  :pagination="pagination"
                  :options="options"
                  @update:options="updateOptions"
                  :items-per-page-options="pages"
                  items-per-page-text=""
                />
              </v-col>
            </v-row>
          </template>
        </v-data-table>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import axios from "axios";
import XLSX from "xlsx";
import _ from "lodash";

export default {
  name: "CheckDuplicate",
  components: {},
  data() {
    return {
      email_tel: "email",
      hmmonth: 6,
      list_email: "",
      file_emails: [],
      in_db: [],
      out_db: [],
      message: "",
      snackbar: false,
      loading: false,
      activeRequests: 0,
      duplicate_leads: [],
      d_statuses: [],
      d_providers: [],
      d_offices: [],
      filter_status: [],
      filter_provider: [],
      filter_office: [],
      expanded: [],
      selectedTop: [],
      duplicate_leads_headers: [
        { text: "Имя", value: "name" },
        { text: "Email", value: "email" },
        { text: "Телефон.", align: "start", value: "tel" },
        { text: "Поставщик", value: "provider_name" },
        { text: "Оффис", value: "office_name" },
        { text: "Менеджер", value: "user_name" },
        { text: "Создан", value: "date_created" },
        { text: "Статус", value: "status_name" },
        { text: "Изменён", value: "date_updated" },
        { text: "Депозит", value: "deposit" },
        { text: "Сообщение", value: "text" },
        { text: "Звонков", value: "qtytel" },
        { text: "ПЕРЕЗВОН", value: "ontime" },
        { text: "Pending", value: "pending" },
        { text: "Депозит", value: "depozit" },
      ],
    };
  },
  computed: {
    filtereduplicate_leads() {
      return this.duplicate_leads.filter((i) => {
        return (
          (this.filter_status.length == 0 ||
            this.filter_status.includes(i.status_id)) &&
          (this.filter_provider.length == 0 ||
            this.filter_provider.includes(i.provider_id)) &&
          (this.filter_office.length == 0 ||
            this.filter_office.includes(i.office_id))
        );
      });
    },
  },
  methods: {
    filter(evt) {
      evt = evt ? evt : window.event;
      let expect = evt.target.value.toString() + evt.key.toString();
      if (!/^[-+]?[0-9]*\.?[0-9]*$/.test(expect)) {
        evt.preventDefault();
      } else {
        return true;
      }
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
      vm.duplicate_leads = [];
      vm.in_db = [];
      vm.out_db = [];
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
            "<br>Дубликатов: " +
            vm.in_db.length;
          vm.snackbar = true;
          vm.duplicate_leads = res.data.leads;
          vm.duplicate_leads = vm.duplicate_leads.map((dl) => {
            dl.date_created = dl.created_at.substring(0, 10);
            dl.date_updated = dl.updated_at.substring(0, 10);
            return dl;
          });
          let a_status = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.status_id;
            })
          );
          vm.d_statuses = vm.statuses
            ? vm.statuses.filter((i) => {
                return a_status.includes(i.id);
              })
            : [];
          let a_hm = _.groupBy(vm.duplicate_leads, "status_id");
          vm.d_statuses.map((el) => {
            el.hm = a_hm[el.id].length;
          });
          let a_prov = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.provider_id;
            })
          );
          vm.d_providers = vm.providers
            ? vm.providers.filter((i) => {
                return a_prov.includes(i.id);
              })
            : [];
          let a_offices = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.office_id;
            })
          );
          vm.d_offices = vm.offices
            ? vm.offices.filter((i) => {
                return a_offices.includes(i.id);
              })
            : [];
          vm.list_email = "";
          vm.file_emails = [];
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
      if (self.email_tel === "tel") {
        unique = self.out_db.map((i) => ({
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
        unique = self.out_db.map((i) => ({ email: i }));
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
        self.$attrs.user &&
        self.$attrs.user.role_id == 1 &&
        self.$attrs.user.office_id == 0
      ) {
        con = con.concat(unique, dup_not);
        window["con"] = XLSX.utils.json_to_sheet(con);
        XLSX.utils.book_append_sheet(wb, window["con"], "CHECK_TO_UPLOAD");
      }
      window["unique"] = XLSX.utils.json_to_sheet(unique);
      XLSX.utils.book_append_sheet(wb, window["unique"], "unique");
      XLSX.utils.book_append_sheet(wbuni, window["unique"], "unique");
      if (
        self.$attrs.user &&
        self.$attrs.user.role_id == 1 &&
        self.$attrs.user.office_id == 0
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
        self.$attrs.user &&
        self.$attrs.user.role_id == 1 &&
        self.$attrs.user.office_id == 0
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

