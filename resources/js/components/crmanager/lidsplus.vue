<template>
  <div>
    <v-container fluid>
      <v-row>
        <v-col cols="1">
          <p>Сброс</p>
          <v-btn @click="clearFilter" class="border" outlined rounded
            ><v-icon>close</v-icon></v-btn
          >
        </v-col>
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
                      getLidsOnDate();
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
                      getLidsOnDate();
                    "
                  ></v-date-picker>
                </v-menu>
              </v-col>

              <v-btn @click="cleardate" small text
                ><v-icon>close</v-icon></v-btn
              >
              <v-checkbox v-model="savedates"></v-checkbox>
            </v-row>
          </div>
        </v-col>

        <v-col>
          <!-- statuses_lids -->
          <p>Фильтр по статусам</p>
          <v-select
            ref="filterStatus"
            color="red"
            v-model="filterStatus"
            :items="statuses"
            item-text="name"
            item-value="id"
            outlined
            rounded
          >
            <template v-slot:selection="{ item }">
              <i
                :style="{
                  background: item.color,
                  outline: '1px solid grey',
                }"
                class="sel_stat mr-4"
              ></i
              >{{ item.name }}
            </template>
            <template v-slot:item="{ item }">
              <i
                :style="{
                  background: item.color,
                  outline: '1px solid grey',
                }"
                class="sel_stat mr-4"
              ></i
              >{{ item.name }}
            </template>
          </v-select>
          <!--  <v-btn
            v-if="
              filterStatus &&
              $props.user.role_id == 1 &&
              filteredItems.length > 0
            "
          >
             <v-icon small @click="deleteItem()"> mdi-delete </v-icon>
          </v-btn>-->
        </v-col>

        <v-col>
          <p>Фильтр по поставщикам</p>
          <v-select
            v-model="filterProviders"
            :items="providers"
            item-text="name"
            item-value="id"
            @change="filterStatuses"
            outlined
            rounded
          ></v-select>
        </v-col>
        <v-col>
          <p>Телефон</p>
          <v-text-field
            v-model.lazy.trim="filtertel"
            append-icon="mdi-phone"
            @input="filterStatuses"
            outlined
            rounded
          ></v-text-field>
        </v-col>

        <v-col>
          <p>Глобальный поиск</p>
          <v-text-field
            v-model="searchAll"
            append-icon="mdi-magnify"
            @click:append="searchInDB"
            outlined
            rounded
          ></v-text-field>
        </v-col>
      </v-row>
    </v-container>
    <v-row>
      <v-col>
        <div class="wrp__statuses">
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
      <v-col cols="9">
        <div class="border pa-4">
          <v-data-table
            v-model.lazy.trim="selected"
            id="tablids"
            :headers="headers"
            :search="search"
            :single-select="false"
            item-key="id"
            show-select
            show-expand
            @click:row="clickrow"
            :items="filteredItems"
            :expanded="expanded"
            ref="datatable"
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
                <v-col cols="1">
                  <v-row class="mb-5">
                    <span class="mt-5 d-flex pl-2 align-center border"
                      >Отбор
                      <v-text-field
                        class="mx-2 mt-0 pt-0 talign-center nn"
                        @input="selectRow"
                        :max="filteredItems.length"
                        v-model.number.lazy="hmrow"
                        hide-details="auto"
                        color="#004D40"
                      ></v-text-field>
                    </span>
                  </v-row>
                </v-col>
                <v-col cols="2" class="mt-1">
                  <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Поиск"
                    single-line
                    hide-details
                    class="border px-2"
                  ></v-text-field>
                </v-col>
                <v-col class="wrp_group">
                  <v-row>
                    <v-checkbox
                      v-model="filterGroups"
                      v-for="(groupa, index) in group"
                      :key="index"
                      :value="groupa.id"
                      class="pt-4"
                    >
                      <template v-slot:label>
                        <div class="img">{{ groupa.fio.slice(0, 3) }}</div>
                        <!-- <img
                          :src="'/storage/' + groupa.pic"
                          :alt="groupa.fio"
                          v-if="groupa.pic"
                        /> -->
                      </template>
                    </v-checkbox>
                  </v-row>
                </v-col>

                <!-- <v-spacer></v-spacer> -->
                <v-col cols="3" class="mt-3">
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[50, 10, 100, 250, 500, -1]"
                    :items-per-page-text="''"
                  />
                </v-col>
              </v-row>
            </template>
            <template v-slot:expanded-item="{ headers, item }">
              <!-- :colspan="headers.length" -->
              <td :colspan="headers.length" class="blackborder">
                <!-- <v-row>
                  <v-col cols="12"> -->

                <logtel :lid_id="item.id" :key="item.id" />
                <!-- </v-col>
                </v-row> -->
              </td>
            </template>
            <template v-slot:footer.prepend>
              <v-col cols="2">
                <v-btn outlined rounded @click="exportXlsx" class="border">
                  <v-icon left> mdi-file-excel </v-icon>
                  Скачать таблицу
                </v-btn>
              </v-col>
              <v-spacer></v-spacer>
              <v-col>
                <h6>Назначение статусов</h6>
              </v-col>
              <v-col cols="3">
                <v-select
                  v-model="selectedStatus"
                  :items="statuses"
                  item-text="name"
                  item-value="id"
                  outlined
                  rounded
                >
                  <template v-slot:selection="{ item }">
                    <i
                      :style="{
                        background: item.color,
                        outline: '1px solid grey',
                      }"
                      class="sel_stat mr-4"
                    ></i
                    >{{ item.name }}
                  </template>
                  <template v-slot:item="{ item }">
                    <i
                      :style="{
                        background: item.color,
                        outline: '1px solid grey',
                      }"
                      class="sel_stat mr-4"
                    ></i
                    >{{ item.name }}
                  </template>
                </v-select>
              </v-col>
              <v-col cols="3">
                <v-btn
                  :disable="!selectedStatus && !selected.length"
                  class="border ma-2"
                  outlined
                  rounded
                  @click="changeStatus"
                >
                  Сменить статус
                </v-btn>
              </v-col>
            </template>
          </v-data-table>
        </div>
      </v-col>
      <v-col cols="3">
        <div class="pa-5 w-100 border wrp_users">
          <div class="my-3">Поиск пользователей</div>
          <v-autocomplete
            v-model="selectedUser"
            :items="users"
            label="Выбор"
            item-text="fio"
            item-value="id"
            :return-object="true"
            append-icon="mdi-close"
            outlined
            rounded
            @click:append="
              disableuser = value || 0;
              getLidsOnUserOrDate();
              selectedUser = {};
            "
          ></v-autocomplete>
          <div class="scroll-y">
            <v-list>
              <v-radio-group
                @change="changeLidsUser"
                ref="radiogroup"
                v-model="userid"
                v-bind="users"
                id="usersradiogroup"
              >
                <v-expansion-panels ref="akk" v-model="akkvalue">
                  <v-expansion-panel v-for="(item, i) in group" :key="i">
                    <v-expansion-panel-header>
                      <div
                        height="60"
                        width="60"
                        class="img v-expansion-panel-header__icon mr-1"
                      >
                        {{ item.fio.slice(0, 3) }}
                      </div>
                      <!-- <img
                        class="v-expansion-panel-header__icon mr-1"
                        height="60"
                        width="60"
                        :src="'/storage/' + item.pic"
                        v-if="item.pic"
                      /> -->

                      {{ item.fio }}
                      <div></div>
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <v-row
                        v-for="user in users.filter(function (i) {
                          return i.group_id == item.group_id;
                        })"
                        :key="user.id"
                      >
                        <v-radio
                          :label="user.fio"
                          :value="user.id"
                          :disabled="disableuser == user.id"
                        >
                        </v-radio>

                        <v-btn
                          class="ml-3"
                          small
                          :color="usercolor(user)"
                          @click="
                            disableuser = user.id;
                            getLidsOnUserOrDate();
                          "
                          :value="user.hmlids"
                          :disabled="disableuser == user.id"
                          >{{ user.hmlids }}</v-btn
                        >
                        <v-chip v-if="user.statnew" label small>
                          {{ user.statnew }}
                        </v-chip>
                      </v-row>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-radio-group>
            </v-list>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
import _ from "lodash";
import logtel from "../manager/logtel";
export default {
  props: ["user"],
  data: () => ({
    savedates: true,
    akkvalue: null,
    loading: false,
    selectedUser: {},
    group: null,
    modal: false,
    dateFrom: false,
    dateTo: false,

    dateProps: { locale: "ru-RU", format: "24hr" },
    datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 14))
      .toISOString()
      .substring(0, 10),
    datetimeTo: new Date().toISOString().substring(0, 10),
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    selectedStatus: 0,
    filterStatus: 0,
    filterGStatus: 0,
    filterProviders: 0,
    filterGroups: [],
    selected: [],
    lids: [],
    search: "",
    searchAll: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      { text: "Менеджер", value: "user" },
      { text: "Создан", value: "date_created" },
      { text: "Изменён", value: "date_updated" },
      { text: "Статус", value: "status" },
      { text: "Депозит", value: "depozit" },
      { text: "Сообщение", value: "text" },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
    providers: [],
    showDuplicates: false,
    telsDuplicates: [],
    clickedItemStatuses: [],
    clickedItemTel: "",
    tel: "",
    lid_id: "",
    expanded: [],
    singleExpand: true,
    componentKey: 0,
    Statuses: [],
    hmrow: "",
  }),
  mounted: function () {
    this.getUsers();
    this.getProviders();
    this.getStatuses();
    if (localStorage.filterStatus) {
      this.filterStatus = parseInt(localStorage.filterStatus);
    }
    if (localStorage.savedates) {
      this.savedates = localStorage.savedates == "true" ? true : false;
    }
    if (localStorage.tel) {
      this.filtertel = localStorage.tel;
    }
    if (this.savedates == true) {
      if (localStorage.datetimeFrom) {
        this.datetimeFrom = new Date(localStorage.datetimeFrom)
          .toISOString()
          .substring(0, 10);
      }
      if (localStorage.datetimeTo) {
        this.datetimeTo = new Date(localStorage.datetimeTo)
          .toISOString()
          .substring(0, 10);
      }
      // this.getLidsOnDate();
    }

    if (localStorage.filterProviders) {
      this.filterProviders = parseInt(localStorage.filterProviders);
    }
    // this.setHeader();
  },

  watch: {
    selectedUser(user) {
      if (user == {}) {
        this.userid = null;
        return;
      }
      this.disableuser = user.id;
      // this.getLidsOnDate();
      this.akkvalue = _.findIndex(this.group, { group_id: user.group_id });
    },
    filterStatus(newName) {
      localStorage.filterStatus = newName;
      this.selectRow();
    },
    savedates(newName) {
      localStorage.savedates = newName;
      if (this.disableuser != 0) {
        this.getLidsOnUserOrDate();
      }
    },

    datetimeFrom(newName) {
      if (this.savedates == true) {
        localStorage.datetimeFrom = newName;
      }
    },
    datetimeTo(newName) {
      if (this.savedates == true) {
        localStorage.datetimeTo = newName;
      }
    },
    filtertel(newName) {
      localStorage.tel = newName;
    },
    filterProviders(newName) {
      localStorage.filterProviders = newName;
    },
    filterGStatus: function (newval, oldval) {
      if (newval == 0) {
        //this.getLids(this.$props.user.id);
      } else {
        this.getStatusLids(newval);
      }
    },
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.lids.filter((i) => {
        return (
          (!this.filterStatus || i.status_id == this.filterStatus) &&
          (!this.filterProviders || i.provider_id == this.filterProviders) &&
          (!this.filtertel || reg.test(i.tel)) &&
          (!this.showDuplicates || this.telsDuplicates.includes(i.id)) &&
          (!this.filterGroups.length || this.filterGroups.includes(i.group_id))
        );
      });
    },
  },
  methods: {
    getLidsOnUserOrDate() {
      if (this.savedates == false) {
        let user_id =
          this.disableuser > 0 ? this.disableuser : this.$props.user.id;
        this.getLids(user_id);
      } else {
        this.getLidsOnDate();
      }
    },
    selectRow() {
      this.selected = this.$refs.datatable.internalCurrentItems.slice(
        0,
        this.hmrow
      );
    },
    cleardate() {
      this.datetimeFrom = new Date(
        new Date().setDate(new Date().getDate() - 14)
      )
        .toISOString()
        .substring(0, 10);
      this.datetimeTo = new Date().toISOString().substring(0, 10);
      if (this.savedates != true) {
        localStorage.removeItem("datetimeTo");
        localStorage.removeItem("datetimeFrom");
      }
      if (this.disableuser == 0) {
        this.getLidsOnUserOrDate();
      }
    },

    clearFilter() {
      this.datetimeFrom = new Date(
        new Date().setDate(new Date().getDate() - 14)
      )
        .toISOString()
        .substring(0, 10);
      this.datetimeTo = new Date().toISOString().substring(0, 10);
      this.filterStatus = 0;
      this.filterProviders = 0;
      this.filtertel = "";
      this.disableuser = 0;
      this.getLidsOnUserOrDate();
    },
    getGroup() {
      return _.filter(this.users, function (o) {
        return o.group_id == o.id;
      });
    },
    filterStatuses() {
      const self = this;
      self.Statuses = [];
      let stord = this.filteredItems;
      stord = Object.entries(_.groupBy(stord, "status"));
      stord.map(function (i) {
        //i[0]//name
        //i[1]//array
        let el = self.statuses.find((s) => s.name == i[0]);
        self.Statuses.push({
          name: i[0],
          hm: i[1].length,
          order: el.order,
          color: el.color,
        });
      });
      self.Statuses = _.orderBy(self.Statuses, "order");
    },
    exportXlsx() {
      const self = this;
      const obj = _.groupBy(self.filteredItems, "status");
      const lidsByStatus = Array.from(Object.keys(obj), (k) => [
        `${k}`,
        obj[k],
      ]);

      var wb = XLSX.utils.book_new(); // make Workbook of Excel
      for (let i = 0; i < lidsByStatus.length; i++) {
        // export json to Worksheet of Excel
        // only array possible
        window["list" + i] = XLSX.utils.json_to_sheet(lidsByStatus[i][1]);
        // add Worksheet to Workbook
        // Workbook contains one or more worksheets
        XLSX.utils.book_append_sheet(
          wb,
          window["list" + i],
          lidsByStatus[i][0]
        ); // sheetAName is name of Worksheet
      }
      // export Excel file
      XLSX.writeFile(wb, "book.xlsx"); // name of the file is 'book.xlsx'
    },
    getDuplicates() {
      this.telsDuplicates = this.lids
        .filter(this.duplicatesOnly)
        .map((f) => f.id);
    },
    duplicatesOnly(v1, i1, self) {
      let ndx = self.findIndex(function (v2, i2) {
        // make sure not looking at the same object (using index to verify)
        // use JSON.stringify for object comparison
        return i1 != i2 && v1.tel == v2.tel;
      });
      return i1 != ndx && ndx != -1;
    },
    searchInDB() {
      let self = this;
      axios
        .get("api/Lid/searchlids?search=" + self.searchAll)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.user = self.users.find((u) => u.id == e.user_id).fio;
            e.date_created = e.created_at.substring(0, 10);
            e.date_updated = e.updated_at.substring(0, 10);
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          self.disableuser = 0;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    deleteItem() {
      const self = this;
      let ids = [];
      this.filteredItems.forEach(function (el) {
        ids.push(el.id);
        self.lids.splice(
          self.lids.indexOf(self.lids.find((l) => l.id == el.id)),
          1
        );
      });

      axios
        .post("api/Lid/deletelids", ids)
        .then(function (response) {
          self.getUsers();
          // self.getLids(send.user_id);
          self.filterStatus = 0;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    putSelectedLidsDB() {
      const self = this;
      let send = {};
      send.user_id = this.userid;

      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else if (
        (this.search !== "" || this.filtertel !== "") &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            self.getUsers();
            self.search = "";
            self.filtertel = "";
          })
          .catch(function (error) {
            console.log(error);
          });
      }
      if (this.lids.length == 0) {
        this.files = [];
      }
      this.userid = null;
      this.$refs.radiogroup.lazyValue = null;
      this.getUsers();
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    forceRerender() {
      this.componentKey += 1;
    },
    clickrow(item, row) {
      if (!row.isSelected) {
        this.tel = item.tel;
        this.lid_id = item.id;
        this.expanded = [item];
      } else this.tel = "";
      row.select(!row.isSelected);
      // ===============
      // let self = this;
      // this.clickedItemTel = item.tel;
      // this.clickedItemStatuses = [];
      // axios
      //   .get("/api/StasusesOfId/" + item.id)
      //   .then((res) => {
      //     self.clickedItemStatuses = res.data;
      //   })
      //   .catch((error) => console.log(error));
      // ====================
    },
    changeLidsUser() {
      const self = this;
      let send = {};
      send.user_id = this.userid;
      send.data = [];
      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else {
        console.log("tut");
        this.userid = null;
        return false;
      }
      // if (
      //   (this.search !== "" ||
      //     this.filtertel !== "" ||
      //     this.filterStatus !== 0 ||
      //     this.filterGStatus !== 0) &&
      //   this.$refs.datatable.$children[0].filteredItems.length > 0
      // ) {
      //   send.data = this.$refs.datatable.$children[0].filteredItems;
      // }
      if (self.$props.user.role_id == 2) {
        //CallBack user not change
        send.data = send.data.filter((f) => f.status_id != 9);
      }
      // if (send.data.length == 0) send.data = this.lids;

      axios
        .post("api/Lid/changelidsuser", send)
        .then(function (response) {
          self.search = "";
          // self.filtertel = "";
          self.userid = null;
          self.$refs.radiogroup.lazyValue = null;
          self.selected = [];
          // self.filterStatus = 0;
          self.getUsers();
          self.getLidsOnUserOrDate();
        })
        .catch(function (error) {
          console.log(error);
        });
      self.searchAll = "";
    },
    getUsers() {
      let self = this;
      let get = self.$props.user.role_id == 1 ? "/api/users" : "/api/getusers";
      axios
        .get(get)
        .then((res) => {
          self.users = res.data.map(
            ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              order,
              statnew,
              pic,
            }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              pic,
              group_id,
              order,
              statnew,
            })
          );
          if (self.$props.user.role_id != 1) {
            self.users = self.users.filter(
              (f) => f.group_id == self.$props.user.group_id
            );
          }
          self.group = self.getGroup();
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
          self.statuses.unshift({ name: "выбор", id: 0 });
        })
        .catch((error) => console.log(error));
    },

    getStatusLids(id) {
      let self = this;
      self.filterStatus = 0;
      self.search = "";
      self.filtertel = "";
      axios
        .get("/api/statuslids/" + id)
        .then((res) => {
          // console.log(res.data);
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.date_created = e.created_at.substring(0, 10);
            e.date_updated = e.updated_at.substring(0, 10);
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          if (self.users.length) {
            e.user = self.users.find((u) => u.id == e.user_id).fio;
          }
          if (self.providers.length) {
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
          }
          self.orderStatus();
          self.searchAll = "";
          if (localStorage.filterStatus) {
            self.filterStatus = parseInt(localStorage.filterStatus);
          }
          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    getLocalDateTime(DateTime) {
      return new Date(
        new Date(DateTime).getTime() -
          new Date(DateTime).getTimezoneOffset() * 60 * 1000
      )
        .toJSON()
        .slice(0, 16)
        .replace("T", " ");
    },
    getLidsOnDate() {
      let self = this;
      this.loading = true;

      let data = {};

      if (this.datetimeFrom == "")
        this.datetimeFrom = new Date(
          new Date().setDate(new Date().getDate() - 14)
        )
          .toISOString()
          .substring(0, 10);
      if (this.datetimeTo == "")
        this.datetimeTo = new Date().toISOString().substring(0, 10);
      data.datefrom = this.getLocalDateTime(this.datetimeFrom);
      data.dateto = this.getLocalDateTime(this.datetimeTo);

      data.user_id = this.disableuser;

      axios
        .post("/api/getLidsOnDate", data)
        .then((res) => {
          // console.log(res.data);
          self.loading = false;
          self.lids = Object.entries(res.data).map((e) => e[1]);
          self.lids.map(function (e) {
            e.date_created = e.created_at.substring(0, 10);
            e.date_updated = e.updated_at.substring(0, 10);
            if (e.status_id) {
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
            }
            if (self.users.find((u) => u.id == e.user_id)) {
              let luser = self.users.find((u) => u.id == e.user_id);
              e.user = luser.fio;
              e.group_id = luser.group_id;
            }

            if (self.providers.find((p) => p.id == e.provider_id)) {
              e.provider = self.providers.find(
                (p) => p.id == e.provider_id
              ).name;
            }
          });
          self.orderStatus();
          self.searchAll = "";
          if (localStorage.filterStatus) {
            self.filterStatus = parseInt(localStorage.filterStatus);
          }
          if (localStorage.filterProviders) {
            self.filterProviders = parseInt(localStorage.filterProviders);
          }
          // self.lidaddates = Object.keys(_.groupBy(self.lids, "date_created"));
          if (self.hmrow > 0) {
            self.hmrow = self.hmrow;
            self.selectRow();
          }
          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    getLids(id) {
      let self = this;
      // self.filterStatus = 0;
      self.search = "";
      // self.filtertel = "";
      self.disableuser = id;
      self.Statuses = [];
      self.loading = true;
      self.lids = [];
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            e.date_created = e.created_at.substring(0, 10);
            e.date_updated = e.updated_at.substring(0, 10);
            if (self.users.find((u) => u.id == e.user_id)) {
              e.user = self.users.find((u) => u.id == e.user_id).fio;
            }
            if (self.providers.find((p) => p.id == e.provider_id)) {
              e.provider = self.providers.find(
                (p) => p.id == e.provider_id
              ).name;
            }
            if (e.status_id)
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
          });
          // self.statuses_lids = _.uniqBy(self.lids, "status_id").map((i) => ({
          //   status_id: i.status_id,
          //   status: i.status,
          // }));
          self.orderStatus();
          self.searchAll = "";
          if (localStorage.filterStatus) {
            self.$refs.filterStatus.selectedItems = [
              self.statuses.find(function (i) {
                return i.id == localStorage.filterStatus;
              }),
            ];
          }
          // self.lidaddates = Object.keys(_.groupBy(self.lids, "date_created"));
          // self.getDuplicates();
          self.loading = false;
          if (self.hmrow > 0) {
            self.hmrow = self.hmrow;
            self.selectRow();
          }
        })
        .catch((error) => console.log(error));
    },
    orderStatus() {
      const self = this;
      let stord = [];
      self.Statuses = [];
      stord = Object.entries(_.groupBy(self.lids, "status"));
      stord.map(function (i) {
        //i[0]//name
        //i[1]//array
        let el = self.statuses.find((s) => s.name == i[0]);
        self.Statuses.push({
          name: i[0],
          hm: i[1].length,
          order: el.order,
          color: el.color,
        });
      });
      self.Statuses = _.orderBy(self.Statuses, "order");
    },
    changeStatus() {
      const self = this;
      let send = {};
      if (this.selected.length && this.selectedStatus) {
        this.selected.map(function (e) {
          e.status_id = self.selectedStatus;
          e.status = self.statuses.find((s) => s.id == e.status_id).name;
        });
        send.data = this.selected.map((e) => e);
        this.changeLids(send);
      }
    },
    changeLids(send) {
      const self = this;
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          self.afterUpdateLids();
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    afterUpdateLids() {
      const self = this;
      self.selected = [];
      self.selectedStatus = 0;
      // self.getLids(self.disableuser);
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
          self.providers.unshift({ name: "выбор", id: 0 });
          // self.getLidsOnDate();
        })
        .catch((error) => console.log(error));
    },
  },
  components: {
    logtel,
  },
};
</script>

<style>
.scroll-y {
  max-height: 60vh;
  overflow: auto;
}
#tablids .v-data-table__wrapper {
  overflow: auto;
  max-height: 54vh;
}
#tablids .v-data-footer .v-data-footer__select {
  margin-left: 0;
  margin-right: 0;
}
#tablids .v-application--is-ltr .v-data-footer__pagination {
  margin: 0;
}
.wrp_date .v-text-field > .v-input__control > .v-input__slot {
  margin-top: 3px;
  margin-bottom: 0;
}
.nn input {
  width: 3rem;
  border-bottom: 1px solid gray;
}
.v-application--is-ltr .v-data-footer__select {
  margin-top: -12px;
}
#usersradiogroup .img,
.wrp_group .img {
  height: 60px;
  width: 60px;
  background: #7620df;
  border-radius: 22px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #fff;
  font-weight: bold;
}
.wrp_group .row {
  gap: 0.7rem;
}
.wrp_group .img {
  height: 34px;
  width: 34px;
}
.v-input--is-label-active .img {
  border: 1px solid #7620df;
  background: #fff;
  color: #7620df;
}
#app .v-application--is-ltr .v-data-footer__pagination {
  margin: 0 12px 0 12px;
}
</style>
