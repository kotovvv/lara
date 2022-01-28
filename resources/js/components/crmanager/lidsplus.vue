<template>
  <div>
    <v-container fluid>
      <v-row>
        <v-col cols="2" class="pt-3 mt-4">
          <v-dialog
            ref="dialog"
            v-model="modal"
            :return-value.sync="dates"
            persistent
            width="290px"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="dates"
                label="Период"
                readonly
                v-bind="attrs"
                v-on="on"
              ></v-text-field>
            </template>
            <v-date-picker v-model="dates" scrollable range locale="ru-RU">
              <v-spacer></v-spacer>
              <v-btn text color="primary" @click="modal = false">
                Отменить
              </v-btn>
              <v-btn
                text
                color="primary"
                @click="
                  $refs.dialog.save(dates);
                  getLidsOnDate();
                "
              >
                Выбрать
              </v-btn>
            </v-date-picker>
          </v-dialog>
        </v-col>
        <v-col cols="2" class="pt-3 mt-4">
          <!-- statuses_lids -->
          <v-select
            ref="filterStatus"
            v-model="filterStatus"
            :items="statuses"
            label="Фильтр по статусам"
            item-text="name"
            item-value="id"
          ></v-select>
          <v-btn
            v-if="
              filterStatus &&
              $props.user.role_id == 1 &&
              filteredItems.length > 0
            "
          >
            <v-icon small @click="deleteItem()"> mdi-delete </v-icon>
          </v-btn>
        </v-col>

        <v-col cols="2" class="pt-3 mt-4">
          <v-select
            v-model="filterProviders"
            :items="providers"
            label="Фильтр по поставщикам"
            item-text="name"
            item-value="id"
          ></v-select>
        </v-col>
        <v-col cols="2">
          <v-card-title>
            <v-text-field
              v-model.lazy.trim="filtertel"
              append-icon="mdi-phone"
              label="Первые цифры телефона"
              single-line
              hide-details
            ></v-text-field>
          </v-card-title>
        </v-col>
        <v-col cols="2">
          <v-card-title>
            <v-text-field
              v-model="searchAll"
              append-icon="mdi-magnify"
              label="Глобальный поиск"
              @click:append="searchInDB"
              single-line
              hide-details
            ></v-text-field>
          </v-card-title>
        </v-col>
        <v-spacer></v-spacer>
        <v-col cols="2" class="pt-3 mt-4">
          <v-select
            v-model="selectedStatus"
            :items="statuses"
            label="Назначение статусов"
            item-text="name"
            item-value="id"
          ></v-select>
          <v-btn
            v-if="selectedStatus && selected.length"
            class="ma-2"
            outlined
            @click="changeStatus"
          >
            Сменить статусы
          </v-btn>
        </v-col>
      </v-row>
    </v-container>

    <v-row>
      <v-col cols="9">
        <v-card>
          <v-data-table
            v-model.lazy.trim="selected"
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
              'items-per-page-options': [50, 10, 100, 250, 500, -1],
              'items-per-page-text': 'Показать',
            }"
          >
            <template
              v-slot:top="{ pagination, options, updateOptions }"
              :footer-props="{
                'items-per-page-options': [50, 10, 100, 250, 500, -1],
                'items-per-page-text': 'Показать',
              }"
            >
              <v-row>
                <v-col cols="2" class="pl-5">
                  <v-select
                    v-model="filterGroups"
                    :items="group"
                    label="По группам"
                    item-text="fio"
                    item-value="id"
                    multiple
                  ></v-select>
                </v-col>
                <!-- <v-col cols="2">
                  <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Поиск"
                    single-line
                    hide-details
                    class="ml-3"
                  ></v-text-field>
                </v-col> -->
                <!-- v-if="telsDuplicates.length > 0" -->
                <v-col cols="2">
                  <v-card-title>
                    <v-checkbox
                      v-model="showDuplicates"
                      class="mt-0"
                      @click="getDuplicates"
                    >
                      <template v-slot:label>
                        <v-icon small> mdi-phone </v-icon>
                        <v-icon small> mdi-phone </v-icon>
                      </template>
                      ></v-checkbox
                    >
                  </v-card-title>
                </v-col>
                <v-col cols="6">
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[50, 10, 100, 250, 500, -1]"
                    :items-per-page-text="'Показать'"
                  />
                </v-col>
                <v-col cols="2">
                  <v-btn tile color="success" @click="exportXlsx">
                    <v-icon left> mdi-file-excel </v-icon>
                    XLSX
                  </v-btn>
                </v-col>
              </v-row>
            </template>
            <template v-slot:expanded-item="{ headers, item }">
              <td :colspan="headers.length" class="blackborder">
                <v-row>
                  <v-col cols="12">
                    <logtel :lid_id="item.id" :key="item.id" />
                  </v-col>
                </v-row>
              </td>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
      <v-col cols="3">
        <div class="row">
          <v-card class="pa-5 w-100">
            Укажите пользователя
            <v-card-text class="scroll-y">
              <v-list>
                <v-radio-group
                  @change="changeLidsUser"
                  ref="radiogroup"
                  v-model="userid"
                  v-bind="users"
                  id="usersradiogroup"
                >
                  <v-expansion-panels>
                    <v-expansion-panel v-for="(item, i) in group" :key="i">
                      <v-expansion-panel-header>
                        {{ item.fio }}
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
                            @click="getLids(user.id)"
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
            </v-card-text>
          </v-card>
          <v-card class="mt-5 w-100">
            <v-card-title primary-title>
              <h3>Статусы</h3>
            </v-card-title>
            <v-card-actions>
              <div>
                <template v-for="(i, x) in Statuses">
                  <div class="status_wrp" :key="x">
                    <span :style="{ background: i.color }">{{ i.name }}</span
                    ><b>{{ i.hm }}</b>
                  </div>
                </template>
              </div>
            </v-card-actions>
          </v-card>
          <!-- <v-card class="pa-5 mt-1 w-100">
            <div class="tel">Тел: {{ clickedItemTel }}</div>
            <v-list dense>
              <v-subheader>Статусы лида</v-subheader>
              <v-list-item-group color="primary">
                <v-list-item v-for="(item, i) in clickedItemStatuses" :key="i">
                  <v-list-item-content :style="{ background: item.color }">
                    <v-list-item-title
                      v-text="item.name + ' ' + item.uname + ' ' + item.cdate"
                    ></v-list-item-title>
                  </v-list-item-content>
                </v-list-item>
              </v-list-item-group>
            </v-list>
          </v-card> -->
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
    modal: false,
    dates: [
      new Date(new Date().setDate(new Date().getDate() - 7))
        .toISOString()
        .substr(0, 10),
      new Date().toISOString().substr(0, 10),
    ],
    datetime: "",
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    // statuses_lids: [],
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
  }),
  mounted: function () {
    this.getUsers();
    this.getProviders();
    this.getStatuses();
    // this.getLidsOnDate();
    if (localStorage.filterStatus) {
      this.filterStatus = localStorage.filterStatus;
    }
    if (localStorage.dates) {
 this.dates = localStorage.dates
    }
    if (localStorage.filterProviders) {
      this.filterProviders = localStorage.filterProviders

    }
  },

  watch: {
    filterStatus(newName) {
      localStorage.filterStatus = newName;
    },
    dates(newName){
      localStorage.dates = newName;
    },
    filterProviders(newName){
localStorage.filterProviders = newName;
    },
    // filteredItems: function () {
    //   const self = this;
    //   self.statuses_lids = _.uniqBy(self.filteredItems, "status_id").map(
    //     (i) => ({
    //       status_id: i.status_id,
    //       status: i.status,
    //     })
    //   );
    //   self.statuses_lids.unshift({ status: "выбор", status_id: 0 });
    // },
    filterGStatus: function (newval, oldval) {
      if (newval == 0) {
        //this.getLids(this.$props.user.id);
      } else {
        this.getStatusLids(newval);
      }
    },
  },
  computed: {
    group() {
      // return _.uniqBy(this.users, "group_id").filter((i) => i.group_id > 0);
      return _.filter(this.users, function (o) {
        return o.group_id == o.id;
      });
    },
    filteredItems() {
      // if (this.showDuplicates && this.telsDuplicates.length > 0)
      //   return this.telsDuplicates;
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
      let cur_user_id = this.disableuser;
      let send = {};
      send.user_id = this.userid;

      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.selected.length > 0 && this.$refs.datatable.items.length > 0) {
        send.data = this.selected;
      } else if (
        (this.search !== "" ||
          this.filtertel !== "" ||
          this.filterStatus !== 0 ||
          this.filterGStatus !== 0) &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
      }
      if (self.$props.user.role_id == 2) {
        //CallBack user not change
        send.data = send.data.filter((f) => f.status_id != 9);
      }
      axios
        .post("api/Lid/changelidsuser", send)
        .then(function (response) {
          self.search = "";
          // self.filtertel = "";
          self.userid = null;
          self.$refs.radiogroup.lazyValue = null;
          self.selected = [];
          self.filterStatus = 0;
          self.getUsers();
          // self.getLids(cur_user_id);
          self.getLidsOnDate();
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
            ({ name, id, role_id, fio, hmlids, group_id, order, statnew }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              order,
              statnew,
            })
          );
          // self.users.sort(function (a, b) {
          //   if (a.order > b.order) {
          //     return 1;
          //   }
          //   if (a.order < b.order) {
          //     return -1;
          //   }
          //   return 0;
          // });
          if (self.$props.user.role_id != 1) {
            self.users = self.users.filter(
              (f) => f.group_id == self.$props.user.group_id
            );
          }
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
          self.statuses.unshift({ name: "Default", id: 0 });
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
    getLidsOnDate() {
      let self = this;
      axios
        .get("/api/getLidsOnDate/" + self.dates)
        .then((res) => {
          // console.log(res.data);
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

          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    getLids(id) {
      let self = this;
      self.filterStatus = 0;
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      self.Statuses = [];
      axios
        .get("/api/userlids/" + id)
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
          // self.getDuplicates();
        })
        .catch((error) => console.log(error));
    },
    orderStatus() {
      const self = this;
      let stord = [];
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
          self.getLidsOnDate();
        })
        .catch((error) => console.log(error));
    },
  },
  components: {
    logtel,
  },
};
</script>

<style scoped>
.status_wrp span {
  padding: 5px;
  word-break: break-all;
}

.status_wrp b {
  padding: 0 8px;
}

.status_wrp {
  margin-right: 15px;
  border: 1px solid grey;
  padding: 3px 0;
  margin-bottom: 15px;
}
</style>
