 <template>
  <div>
    <v-snackbar v-model="snackbar" top right timeout="-1">
      <v-card-text v-html="message"></v-card-text>
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>
    <v-tabs v-model="tab" background-color="primary" dark>
      <v-tab> Провайдер </v-tab>
      <v-tab v-if="$attrs.user.role_id == 1 && $attrs.user.group_id == 0">
        ВТС
      </v-tab>
      <v-tab>CHECK DUBLIKATE MAIL</v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
      <v-tab-item>
        <v-row>
          <v-col cols="2">
            <v-select
              v-model="selectedProvider"
              :items="providers"
              label="Провайдер"
              item-text="name"
              item-value="id"
            ></v-select>
          </v-col>
          <v-col cols="3" v-if="selectedProvider">
            <v-file-input
              v-model="files"
              ref="fileupload"
              label="загрузить CSV"
              show-size
              truncate-length="24"
              @change="onFileChange"
            ></v-file-input>
          </v-col>
          <v-col cols="3" v-if="parse_csv.length">
            <v-select
              v-model="selectedStatus"
              :items="statuses"
              label="Статус"
              item-text="name"
              item-value="id"
            ></v-select>
          </v-col>
          <v-col cols="4" v-if="parse_csv.length">
            <v-textarea
              v-model="message"
              label="Сообщение"
              rows="1"
            ></v-textarea>
          </v-col>
        </v-row>
        <v-row class="align-center">
          <v-col cols="1">
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
                  label="С даты"
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
                  takedates ? getImports() : '';
                "
              ></v-date-picker>
            </v-menu>
          </v-col>
          <v-col cols="1">
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
                  label="По дату"
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
                  takedates ? getImports() : '';
                "
              ></v-date-picker>
            </v-menu>
          </v-col>
          <v-checkbox v-model="takedates" @change="getImports"></v-checkbox>

          <v-col cols="2">
            <v-select
              v-model="filter_import_provider"
              :items="i_providers"
              label="Фильтр провайдер"
              item-text="name"
              item-value="id"
              outlined
              rounded
              multiple
            >
              <template v-slot:selection="{ item, index }">
                <span v-if="index === 0">{{ item.name }} </span>
                <span v-if="index === 1" class="grey--text text-caption">
                  (+{{ filterProviders.length - 1 }} )
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
            </v-select>
          </v-col>
          <v-spacer></v-spacer>
        </v-row>
        <v-main v-if="parse_csv.length && files">
          <v-row>
            <v-col cols="8">
              <v-card>
                <v-container>
                  <v-row>
                    <v-col cols="4">
                      <v-card-title>
                        <v-text-field
                          v-model="search"
                          append-icon="mdi-magnify"
                          label="Поиск"
                          single-line
                          hide-details
                        ></v-text-field>
                      </v-card-title>
                    </v-col>
                    <v-col cols="3">
                      <v-card-title>
                        <v-text-field
                          v-model.lazy.trim="filtertel"
                          append-icon="mdi-phone"
                          label="Начальные цифры"
                          single-line
                          hide-details
                        ></v-text-field>
                      </v-card-title>
                    </v-col>
                  </v-row>
                </v-container>
                <v-data-table
                  v-model.lazy.trim="selected"
                  :headers="headers"
                  :search="search"
                  :single-select="false"
                  item-key="tel+afilyator"
                  show-select
                  :items="filteredItems"
                  ref="datatable"
                  :loading="loading"
                ></v-data-table>
              </v-card>
            </v-col>
            <v-col cols="4">
              <v-card height="100%" class="pa-5">
                Укажите пользователя для лидов
                <v-list>
                  <v-radio-group
                    @change="putSelectedLidsDB"
                    ref="radiogroup"
                    v-model="userid"
                    v-bind="users"
                    id="usersradiogroup"
                  >
                    <v-radio
                      :value="user.id"
                      v-for="user in users"
                      :key="user.id"
                    >
                      <template v-slot:label>
                        {{ user.fio }}
                        <v-badge
                          :content="user.hmlids"
                          :value="user.hmlids"
                          :color="usercolor(user)"
                          overlap
                        >
                          <v-icon large v-if="user.role_id === 2">
                            mdi-account-group-outline
                          </v-icon>
                          <v-icon large v-else> mdi-account-outline </v-icon>
                        </v-badge>
                      </template>
                    </v-radio>
                  </v-radio-group>
                </v-list>
              </v-card>
            </v-col>
          </v-row>
        </v-main>
        <v-row v-else>
          <v-col cols="12">
            <v-progress-linear
              :active="loading"
              indeterminate
              color="purple"
            ></v-progress-linear>
          </v-col>
          <v-col cols="12">
            <v-data-table
              :headers="import_headers"
              item-key="id"
              :items="filter_imports"
              ref="importtable"
              @click:row="clickrow"
            >
              <template v-slot:item.id="{ item }">
                <v-btn
                  @click.stop="deleteLoad(item)"
                  plain
                  v-if="item.load_key > 0"
                  ><v-icon>mdi-delete</v-icon></v-btn
                >
              </template>
            </v-data-table>
          </v-col>
          <v-col cols="12" v-if="leads.length">
            <v-row>
              <v-col>
                <div class="wrp__statuses" id="wrp_stat">
                  <template v-for="(i, x) in Statuses">
                    <div class="status_wrp" :key="x">
                      <b
                        :style="{
                          background: i.color,
                          outline: '1px solid' + i.color,
                        }"
                        >{{ i.hm }}</b
                      >
                      <span>{{ i.name }}</span>
                    </div>
                  </template>
                </div>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <div class="border pa-4">
                  <v-data-table
                    id="tabimplids"
                    :headers="headers_leads"
                    item-key="id"
                    :items="leads"
                    :footer-props="{
                      'items-per-page-options': [],
                      'items-per-page-text': '',
                    }"
                    :disable-items-per-page="true"
                    :loading="loading"
                    loading-text="Загружаю... Ожидайте"
                  >
                    <template
                      v-slot:top="{ pagination, options, updateOptions }"
                      :footer-props="{
                        'items-per-page-options': [50, 10, 100, 250, 500, -1],
                        'items-per-page-text': '',
                      }"
                    >
                      <v-row>
                        <!-- <v-spacer></v-spacer> -->
                        <v-col cols="3" class="mt-3">
                          <v-data-footer
                            :pagination="pagination"
                            :options="options"
                            @update:options="updateOptions"
                            :items-per-page-options="[
                              50, 10, 100, 250, 500, -1,
                            ]"
                            :items-per-page-text="''"
                          />
                        </v-col>
                      </v-row>
                    </template>
                  </v-data-table>
                </div>
              </v-col>
            </v-row>
          </v-col>
        </v-row>
      </v-tab-item>
      <v-tab-item v-if="$attrs.user.role_id == 1 && $attrs.user.group_id == 0">
        <importBTC></importBTC>
      </v-tab-item>
      <v-tab-item>
        <v-container>
          <v-row>
            <v-col>
              <v-radio-group v-model="email_tel" row>
                <v-radio label="email" value="email"></v-radio>
                <v-radio label="телефон" value="tel"></v-radio>
              </v-radio-group>
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
            <v-col cols="12" v-if="duplicate_leads.length || out_db.length">
              <v-row>
                <v-col>
                  <v-select
                    ref="filterStatus"
                    label="Фильтр статус"
                    color="red"
                    v-model="filter_status"
                    :items="d_statuses"
                    item-text="name"
                    item-value="id"
                    outlined
                    rounded
                    :multiple="true"
                  >
                    <template v-slot:selection="{ item, index }">
                      <span v-if="index === 0">{{ item.name }} </span>
                      <span v-if="index === 1" class="grey--text text-caption">
                        (+{{ filter_status.length - 1 }} )
                      </span>
                    </template>
                    <template v-slot:item="{ item, attrs }">
                      <v-badge
                        :value="attrs['aria-selected'] == 'true'"
                        color="#7620df"
                        dot
                        left
                      >
                        <i
                          :style="{
                            background: item.color,
                            outline: '1px solid grey',
                          }"
                          class="sel_stat mr-4"
                        ></i>
                      </v-badge>
                      {{ item.name }}
                    </template>
                  </v-select></v-col
                >
                <v-col>
                  <v-select
                    v-model="filter_provider"
                    label="Фильтр поставщик"
                    :items="d_providers"
                    item-text="name"
                    item-value="id"
                    outlined
                    rounded
                    multiple
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
                  </v-select></v-col
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
              </v-row>

              <v-data-table
                :headers="duplicate_leads_headers"
                item-key="id"
                :items="filtereduplicate_leads"
                ref="duplicatetable"
              >
              </v-data-table>
            </v-col>
          </v-row>
        </v-container>
      </v-tab-item>
    </v-tabs-items>
    <ConfirmDlg ref="confirm" />
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
import importBTC from "./importBTC";
import _ from "lodash";
export default {
  data: () => ({
    takedates: 0,
    dateFrom: false,
    dateTo: false,
    dateProps: { locale: "ru-RU", format: "24hr" },
    datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 3))
      .toISOString()
      .substring(0, 10),
    datetimeTo: new Date().toISOString().substring(0, 10),
    list_email: "",
    file_emails: [],
    in_db: [],
    out_db: [],
    message: "",
    snackbar: false,
    loading: false,
    userid: null,
    users: [],
    filter_status: [],
    filter_provider: [],
    filter_import_provider: [],
    filter_office: [],
    providers: [],
    statuses: [],
    offices: [],
    i_providers: [],
    d_providers: [],
    d_statuses: [],
    d_offices: [],
    imports: [],
    selectedStatus: 8,
    selectedProvider: 0,
    related_user: [],
    selected: [],
    files: [],
    search: "",
    filtertel: "",
    headers_leads: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      // { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      // { text: "Менеджер", value: "user" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
    ],
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Тел.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
    ],
    import_headers: [
      { text: "", value: "id" },
      { text: "Дата час початку", value: "start" },
      { text: "Дата час закінчення", value: "end" },
      { text: "Поставщик", value: "provider" },
      { text: "Хто імпортував", value: "user" },
      { text: "Коментар", value: "message" },
    ],
    duplicate_leads_headers: [
      { text: "Имя", value: "name" },
      { text: "Емаил", value: "email" },
      { text: "Тел", value: "tel" },
      { text: "Создан", value: "created" },
      { text: "Обновлён", value: "updated" },
      { text: "Статус", value: "status_name" },
      { text: "Оператор", value: "user_name" },
      { text: "Оффис", value: "office_name" },
      { text: "Провайдер", value: "provider_name" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Сообщение", value: "text" },
    ],
    duplicate_leads: [],
    parse_header: [],
    parse_csv: [],
    sortOrders: {},
    sortKey: "tel",
    tab: 0,
    Statuses: [],
    leads: [],
    email_tel: "email",
  }),
  watch: {
    selectedProvider: function (newval) {
      this.users = [];
      this.related_user = [];
      let rel_user = this.providers.find(
        (p) => p.id == newval
      ).related_users_id;
      if (rel_user.length > 2) {
        this.related_user = JSON.parse(rel_user);
        this.getUsers();
      }
    },
  },
  mounted() {
    this.getImports();
    this.getProviders();
    this.getOffices();
    this.getStatuses();
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.parse_csv.filter((i) => {
        return !this.filtertel || reg.test(i.tel);
      });
    },
    filter_imports() {
      return this.imports.filter((i) => {
        return (
          this.filter_import_provider.length == 0 ||
          this.filter_import_provider.includes(i.provider_id)
        );
      });
    },
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
    getOffices() {
      let self = this;
      if (self.$attrs.user.role_id == 1 && self.$attrs.user.office_id == 0) {
        axios
          .get("/api/getOffices")
          .then((res) => {
            self.offices = res.data;
            self.offices.push(self.offices[0].id);
          })
          .catch((error) => console.log(error));
      } else {
        self.offices.push(self.$attrs.user.office_id);
      }
    },
    exportXlsx() {
      const self = this;
      let unique = [];
      const obj = _.groupBy(self.filteredItems, "status");
      const lidsByStatus = Array.from(Object.keys(obj), (k) => [
        `${k}`,
        obj[k],
      ]);
      var wb = XLSX.utils.book_new(); // make Workbook of Excel

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
      if (self.email_tel === "tel") {
        let con = [];
        const dup_not = self.filtereduplicate_leads.filter((dd) => {
          return ![10, 11, 23].includes(dd.status_id);
        });
        const dup_call = self.filtereduplicate_leads.filter((dd) => {
          return (
            dd.status_id == 9 &&
            (Date.now() - Date.parse(dd.updated)) / (60 * 60 * 24 * 1000) > 21
          );
        });
        con = con.concat(unique, dup_not, dup_call);
        window["con"] = XLSX.utils.json_to_sheet(con);
        XLSX.utils.book_append_sheet(wb, window["con"], "CHECK_TO_UPLOAD");
      }

      window["unique"] = XLSX.utils.json_to_sheet(unique);
      XLSX.utils.book_append_sheet(wb, window["unique"], "unique");

      window["list"] = XLSX.utils.json_to_sheet(self.filtereduplicate_leads);
      XLSX.utils.book_append_sheet(wb, window["list"], "duplicate");
      if (self.email_tel === "tel") {
        // - 3ая -  все дубли со статусом депозит
        const dup_dep = self.filtereduplicate_leads.filter((dd) => {
          return dd.status_id == 10;
        });
        window["dup_dep"] = XLSX.utils.json_to_sheet(dup_dep);
        XLSX.utils.book_append_sheet(
          wb,
          window["dup_dep"],
          "duplicate_doposit"
        );
        // - 4ая - все дубли со статусом колбек
        const dup_cb = self.filtereduplicate_leads.filter((dd) => {
          return dd.status_id == 9;
        });
        window["dup_cb"] = XLSX.utils.json_to_sheet(dup_cb);
        XLSX.utils.book_append_sheet(
          wb,
          window["dup_cb"],
          "duplicate_callback"
        );
        // - 5ая - все дубли которые имеют статус -  deposit, callback, trash, badphone моложе трех недель)
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
      // export Excel file
      XLSX.writeFile(
        wb,
        "dupl_" + self.email_tel + new Date().toDateString() + ".xlsx"
      ); // name of the file is 'book.xlsx'
    },
    checkEmails() {
      let vm = this;
      vm.loading = true;
      vm.snackbar = false;
      vm.message = "";
      vm.duplicate_leads = [];
      vm.in_db = [];
      vm.out_db = [];
      let data = {};
      data.emails = vm.list_email
        .replace(/[\r]/gm, "")
        .replaceAll(" ", "")
        .split("\n");
      data.check = 1;
      data.email_tel = vm.email_tel;
      axios
        .post("api/checkEmails", data)
        .then(function (res) {
          vm.in_db = res.data.emails.filter((n) => n);

          vm.out_db = [
            ...new Set(
              data.emails.filter((i) => !vm.in_db.includes(i.toLowerCase()))
            ),
          ];
          vm.message =
            "Уникальных: " +
            vm.out_db.length +
            "<br>Дубликатов: " +
            vm.in_db.length;
          vm.snackbar = true;
          vm.duplicate_leads = res.data.leads;
          let a_status = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.status_id;
            })
          );
          vm.d_statuses = vm.statuses.filter((i) => {
            return a_status.includes(i.id);
          });
          let a_prov = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.provider_id;
            })
          );
          vm.d_providers = vm.providers.filter((i) => {
            return a_prov.includes(i.id);
          });
          let a_offices = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.office_id;
            })
          );
          vm.d_offices = vm.offices.filter((i) => {
            return a_offices.includes(i.id);
          });

          vm.loading = false;
          vm.list_email = "";
          vm.files = [];
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    filterStatuses() {
      const self = this;
      self.Statuses = [];
      let stord = this.leads;
      stord = Object.entries(_.groupBy(stord, "status"));
      stord.map(function (i) {
        //i[0]//name
        //i[1]//array
        let el = self.statuses.find((s) => s.name == i[0]);
        self.Statuses.push({
          id: el.id,
          name: i[0],
          hm: i[1].length,
          order: el.order,
          color: el.color,
        });
      });
      self.Statuses = _.orderBy(self.Statuses, "order");
      setTimeout(() => {
        const el = document.getElementById("wrp_stat");
        if (el) {
          el.scrollIntoView({ behavior: "smooth" });
        }
      }, 100);
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    putSelectedLidsDB() {
      let self = this;
      self.loading = true;
      let send = {};
      send.user_id = this.userid;
      send.provider_id = this.selectedProvider;
      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.$refs.datatable.items.length > 0) {
        if (this.selected.legth) send.data = this.selected;
        else send.data = this.$refs.datatable.items;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            // console.log(response);
            if (self.selected.length) {
              self.parse_csv = self.parse_csv.filter(
                (ar) => !self.selected.find((rm) => rm.tel === ar.tel)
              );
            } else {
              self.parse_csv = [];
            }
            self.selected = [];
            self.getUsers();
            self.loading = false;
            self.files = [];
            // save to imports db
            //======================
            let info = {};

            info.start = response.data.date_start;
            info.end = response.data.date_end;
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports/0/0", info)
              .then(function (response) {
                self.getImports();
              })
              .catch(function (error) {
                console.log(error);
              });
          })
          .catch(function (error) {
            console.log(error);
          });
      } else if (
        (this.search !== "" || this.filtertel !== "") &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            self.parse_csv = self.parse_csv.filter(
              (ar) =>
                !self.$refs.datatable.$children[0].filteredItems.find(
                  (rm) => rm.tel === ar.tel
                )
            );
            self.getUsers();
            self.search = "";
            self.filtertel = "";
            self.loading = false;
            // save to imports db
            //======================
            let info = {};

            info.start = response.data.date_start
              .substring(0, 19)
              .replace("T", " ");
            info.end = response.data.date_end
              .substring(0, 19)
              .replace("T", " ");
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports", info)
              .then(function (response) {
                // console.log(response);
                self.getImports();
              })
              .catch(function (error) {
                console.log(error);
              });
          })
          .catch(function (error) {
            console.log(error);
          });
      }
      if (this.parse_csv.length == 0) {
        this.files = [];
        this.selectedProvider = "";
      }
      this.userid = null;
      this.$refs.radiogroup.lazyValue = null;
      this.getUsers();
    },
    clickrow(item) {
      console.log(item);
      let self = this;
      let data = {};
      self.leads = [];
      self.Statuses = [];
      self.loading = true;
      data.provider_id = item.provider_id;
      data.start = item.start;
      data.end = item.end;

      axios
        .post("api/getlidsImportedProvider", data)
        .then(function (response) {
          self.leads = response.data;
          self.leads.map(function (e) {
            e.date_created = e.created_at.substring(0, 10);
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          self.loading = false;
          if (self.leads.length) {
            self.leads = self.leads.map((el) => {
              el.provider = self.providers.find(
                (pel) => pel.id == el.provider_id
              ).name;
              return el;
            });
            self.filterStatuses();
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id, related_users_id }) => ({
            name,
            id,
            related_users_id,
          }));
        })
        .catch((error) => console.log(error));
    },
    async deleteLoad(item) {
      const self = this;
      if (
        await this.$refs.confirm.open(
          "Удалить???",
          "Все импортированные лиды по указанной записи будут удалены безвозвратно. Удаляем?"
        )
      ) {
        axios
          .get("api/deleteLoad/" + item.load_key)
          .then(function (response) {
            self.getImports();
          })
          .catch(function (error) {
            console.log(error);
          });
      }
    },
    getImports() {
      let self = this;
      self.loading = true;
      let datefrom = self.datetimeFrom,
        dateto = self.datetimeTo;
      if (self.takedates == 0) {
        datefrom = 0;
        dateto = 0;
      }
      axios
        .get("/api/imports/" + datefrom + "/" + dateto)
        .then((res) => {
          self.imports = res.data.map(
            ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
              load_key,
            }) => ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
              load_key,
            })
          );
          let a_prov = _.uniq(
            _.map(self.imports, (el) => {
              return el.provider_id;
            })
          );
          self.i_providers = self.providers.filter((i) => {
            return a_prov.includes(i.id);
          });

          if (self.$attrs.user.group_id > 0) {
            self.imports = self.imports.filter(
              (i) => i.group_id == self.$attrs.user.group_id
            );
          }
          self.loading = false;
        })
        .catch((error) => console.log(error));
    },
    getUsers() {
      let self = this;

      axios
        .post("/api/getusers", self.related_user)
        .then((res) => {
          self.users = res.data.map(({ name, id, role_id, fio, hmlids }) => ({
            name,
            id,
            role_id,
            fio,
            hmlids,
          }));
        })
        .catch((error) => console.log(error));
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
    txt2Array(txt) {
      let vm = this;
      vm.list_email = txt;
    },
    csvJSON(csv) {
      var vm = this;
      var lines = csv.split("\n");
      var result = [];
      var headers = lines[0].split(";");
      headers = ["name", "email", "tel", "afilyator"];
      // vm.parse_header = lines[0].split(",");
      // lines[0].split(",").forEach(function (key) {
      //   vm.sortOrders[key] = 1;
      // });

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
          console.log(vm.tab);
          if (vm.tab == 2) {
            vm.parse_txt_emails = vm.txt2Array(vm.fileinput);
          } else {
            vm.parse_csv = vm
              .csvJSON(this.fileinput)
              .filter(
                (v, i, a) =>
                  a.findIndex(
                    (t) => t.afilyator + t.tel == v.afilyator + v.tel
                  ) === i
              );
          }
        },
        (error) => {
          /* handle an error */
          console.log(error);
        }
      );
    },
  },
  components: {
    importBTC,
    ConfirmDlg: () => import("./ConfirmDlg"),
  },
};
</script>
