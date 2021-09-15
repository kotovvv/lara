<template>
  <div>
    <v-row>
      <v-col cols="10">
        <v-row>
          <v-col cols="3">
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
          <v-col cols="3" class="pt-3 mt-4">
            <v-select
              v-model="filterProviders"
              :items="providers"
              label="Фильтр по поставщикам"
              item-text="name"
              item-value="id"
            ></v-select>
          </v-col>
          <v-col cols="3">
            <v-card-title>
              <v-text-field
                v-model.lazy.trim="filtertel"
                append-icon="mdi-phone"
                label="Первые цифры номера"
                single-line
                hide-details
              ></v-text-field>
            </v-card-title>
          </v-col>
          <v-col cols="3" class="pt-3 mt-4">
            <v-select
              v-model="filterStatus"
              :items="filterstatuses"
              label="Фильтр по статусу"
              item-text="name"
              item-value="id"
            >
              <template v-slot:item="{ item }">
                <div
                  :style="{ background: item.color, width: '100%' }"
                  v-text="item.name"
                ></div>
              </template>
            </v-select>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <v-card>
              <v-data-table
                id="ontime"
                v-model.lazy.trim="selected"
                :headers="headers"
                :single-select="true"
                item-key="id"
                show-select
                :items="todayItems"
                ref="todaytable"
                :expanded="expanded"
                @click:row="clickrow"
                hide-default-header
                hide-default-footer
              >
                <template v-slot:item.name="{ item }">
                  <div :style="stylecolor(item.status_id)">{{ item.name }}</div>
                </template>
                <template v-slot:item.email="{ item }">
                  <div :style="stylecolor(item.status_id)">
                    {{ item.email }}
                  </div>
                </template>
                <template v-slot:item.date="{ item }">
                  <div :style="stylecolor(item.status_id)">{{ item.date }}</div>
                </template>
                <template v-slot:item.tel="{ item }">
                  <a
                    class="tel"
                    @click.stop
                    :href="'sip:' + item.tel"
                    :style="stylecolor(item.status_id)"
                    >{{ item.tel }}</a
                  >
                </template>

                <template v-slot:item.afilyator="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.afilyator }}
                  </div>
                </template>
                <template v-slot:item.provider="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.provider }}
                  </div>
                </template>
                <template v-slot:item.status="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.status }}
                  </div>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <v-row>
                      <v-col cols="8">
                        <v-textarea
                          class="mx-2"
                          label="Сообщение"
                          rows="1"
                          prepend-icon="mdi-comment"
                          v-model="text"
                          :value="text"
                          @keyup.enter.native="changemes(item)"
                        ></v-textarea>
                      </v-col>
                      <v-col cols="4">
                        <v-datetime-picker
                          label="Дата/время"
                          v-model="datetime"
                          :datetime="datetime"
                        >
                        </v-datetime-picker>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col cols="12">
                        <logtel :lid_id="lid_id" />
                      </v-col>
                    </v-row>
                  </td>
                </template>
              </v-data-table>
            </v-card>
            <v-card>
              <v-data-table
                id="maintable"
                v-model.lazy.trim="selected"
                :headers="headers"
                :search="search"
                :single-select="true"
                item-key="id"
                show-select
                :items="filteredItems"
                ref="datatable"
                :expanded="expanded"
                :footer-props="{
                  'items-per-page-options': [10, 50, 100, 250, 500, -1],
                  'items-per-page-text': 'Показать',
                }"
                @click:row="clickrow"
              >
                <template
                  v-slot:top="{ pagination, options, updateOptions }"
                  :footer-props="{
                    'items-per-page-options': [10, 50, 100, 250, 500, -1],
                    'items-per-page-text': 'Показать',
                  }"
                >
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[10, 50, 100, 250, 500, -1]"
                    :items-per-page-text="'Показать'"
                  />
                </template>
                <template v-slot:item.name="{ item }">
                  <div :style="stylecolor(item.status_id)">{{ item.name }}</div>
                </template>
                <template v-slot:item.email="{ item }">
                  <div :style="stylecolor(item.status_id)">
                    {{ item.email }}
                  </div>
                </template>
                <template v-slot:item.date="{ item }">
                  <div :style="stylecolor(item.status_id)">{{ item.date }}</div>
                </template>
                <template v-slot:item.tel="{ item }">
                  <a
                    class="tel"
                    @click.stop
                    :href="'sip:' + item.tel"
                    :style="stylecolor(item.status_id)"
                    >{{ item.tel }}</a
                  >
                </template>
                <template v-slot:item.afilyator="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.afilyator }}
                  </div>
                </template>
                <template v-slot:item.provider="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.provider }}
                  </div>
                </template>
                <template v-slot:item.date_created="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.date_created }}
                  </div>
                </template>
                <template v-slot:item.status="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.status }}
                  </div>
                </template>
                <template v-slot:item.text="{ item }">
                  <div class="px-1" :style="stylecolor(item.status_id)">
                    {{ item.text }}
                  </div>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <v-row>
                      <v-col cols="8">
                        <v-textarea
                          class="mx-2"
                          label="Сообщение"
                          rows="2"
                          prepend-icon="mdi-comment"
                          v-model="text"
                          :value="text"
                          @keyup.enter.native="changemes(item)"
                        ></v-textarea>
                        <!-- @change="changemes(item)" -->
                      </v-col>
                      <v-col cols="4">
                        <v-datetime-picker
                          ref="datetime"
                          label="Дата/время"
                          @input="setTime"
                          v-model="datetime"
                          :datetime="datetime"
                        >
                        </v-datetime-picker>
                      </v-col>
                    </v-row>
                    <v-row>
                      <v-col cols="12">
                        <logtel :lid_id="lid_id" :key="componentKey" />
                      </v-col>
                    </v-row>
                  </td>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="2">
        <v-card height="100%" class="pa-5">
          <v-list>
            <v-radio-group
              @change="putSelectedLidsDB"
              ref="radiogroup"
              id="statusesradiogroup"
              v-model="selectedStatus"
              :disabled="selected.length == 0"
            >
              <!-- v-bind="selected.length?selected[0].status_id:null" -->
              <!-- v-model="selectedStatus" -->
              <v-radio
                :label="status.name"
                :value="status.id"
                v-for="status in statuses"
                :key="status.id"
              >
                <span
                  slot="label"
                  class="px-1"
                  :style="{ background: status.color, width: '100%' }"
                  >{{ status.name }}</span
                >
              </v-radio>
            </v-radio-group>
          </v-list>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
import logtel from "./logtel";
export default {
  components: {
    logtel,
  },
  props: ["user"],
  data: () => ({
    componentKey: 0,
    text: null,
    tel: "",
    lid_id:'',
    expanded: [],
    singleExpand: true,
    datetime: "",
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    filterstatuses: [],
    selectedStatus: 0,
    filterStatus: 0,
    filterProviders: 0,
    providers: [],
    selected: [],
    todayItems: [],
    lids: [],
    search: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
      { text: "Дата", value: "date" },
      { text: "Сообщение", value: "text" },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
  }),

  mounted: function () {
    this.getProviders();
    this.getStatuses();
  },
  watch: {
    selected: function (newval, oldval) {
      //  console.log(newval)
      //  console.log(oldval)
      if (this.selected.length == 0) {
        this.selectedStatus = null;
        this.expanded = [];
        this.tel = "";
      } else {
        this.selectedStatus = newval[0].status_id;
        this.expanded = this.selected;
        this.datetime =
          newval[0].ontime != "0000-00-00 00:00:00" && newval[0].ontime != null
            ? newval[0].ontime.substring(0, 16)
            : "";
      }
    },
    datetime: function (newval, oldval) {
      if ((newval == null || newval != oldval) && this.tel !='') {
        this.setTime();
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
          (!this.filtertel || reg.test(i.tel))
        );
      });
    },
  },
  methods: {
    forceRerender() {
      this.componentKey += 1;
    },
    setTime() {
      const self = this;
      let send = {};

      send.ontime =  (this.datetime == null)? "" :this.datetime.toString().length > 0 && this.datetime.toString().length > 20? new Date((this.datetime).getTime()- ((this.datetime).getTimezoneOffset()*60000)).toISOString() : this.datetime;

      // if (this.datetime == null) send.ontime = "";
      send.id = this.lid_id;
      self.selected = [];
      axios
        .post("api/Lid/ontime", send)
        .then(function (response) {
          //console.log(response);
          self.getLids(self.$props.user.id);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    currentDateTime() {
      const date = new Date();
      // 01, 02, 03, ... 29, 30, 31
      const dd = (date.getDate() < 10 ? "0" : "") + date.getDate();
      // 01, 02, 03, ... 10, 11, 12
      const MM = (date.getMonth() + 1 < 10 ? "0" : "") + (date.getMonth() + 1);
      // 1970, 1971, ... 2015, 2016, ...
      const yyyy = date.getFullYear();
      const time = date.getHours() + ":" + date.getMinutes();
      // create the format you want
      return yyyy + "-" + MM + "-" + dd + " " + time;
    },
    changemes(eli) {
      const self = this;
      let send = {};
      let send_el = {};
      send_el.tel = eli.tel;
      send_el.lid_id = eli.id;
      send_el.text = self.text;
      send_el.user_id = eli.user_id;
      send.lid_id = eli.id;
      send.data = send_el;

      axios
        .post("api/log/add", send_el)
        .then(function (response) {
          // console.log(response);
          self.forceRerender();
          self.text = null;
          self.lids.find((f) => f.id == send_el.lid_id).text = send_el.text
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    putSelectedLidsDB() {
      const self = this;
      let send = {};
      let send_el = {};
      let costil = self.filtertel;
      self.filtertel = 1;
      self.filtertel = costil;

      let eli = self.lids.find((obj) => obj.id == self.selected[0].id);

      eli.status = self.statuses.find((s) => s.id == self.selectedStatus).name;
      eli.status_id = self.selectedStatus;
      eli.updated_at = self.currentDateTime();
      send.id = eli.id;
      send_el.id = eli.id;
      send_el.tel = eli.tel;
      send_el.status_id = self.selectedStatus;
      send_el.user_id = eli.user_id;
      send.data = [];
      send.data.push(send_el);
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          self.forceRerender();
          // console.log(response);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },

    clickrow(item, row) {

      if (!row.isSelected) {this.tel = item.tel;this.lid_id = item.id; }
      else this.tel = "";
      row.select(!row.isSelected);
    },

    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ name, id, color }) => ({
            name,
            id,
            color,
          }));
          self.filterstatuses = self.statuses.map((e) => e);
          self.filterstatuses.unshift({ name: "Без статуса", id: 0 });
          self.getLids(self.$props.user.id);
        })
        .catch((error) => console.log(error));
    },

    getLids(id) {
      let self = this;
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            // e.user = self.users.find((u) => u.id === e.user_id).fio;
            // delete e.provider_id;
            e.date = e.updated_at.substring(0, 10);
            e.date_created = e.created_at.substring(0, 10);
            // e.mess = e.text;
            e.provider = self.providers.find((p) => p.id == e.provider_id).name;
            if (e.status_id) {
              e.status =
                self.statuses.find((s) => s.id == e.status_id).name || "";
            }
          });
          self.todaylids();
        })
        .catch((error) => console.log(error));
    },
    todaylids() {
      const self = this;
      self.todayItems = self.lids.filter(function (l) {
        return (
          new Date(l.ontime).toLocaleDateString() ==
          new Date().toLocaleDateString()
        );
      });
      self.todayItems.map(function (t) {
        t.date = new Date(t.ontime).toLocaleTimeString().substring(0, 5);
      });
    },
    stylecolor(status_id) {
      // console.log(status_id)
      if (status_id == null) return;
      return (
        "padding:5px;background:" +
        this.statuses.find((e) => e.id == status_id).color
      );
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
          self.providers.unshift({ name: "Все", id: 0 });
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>

<style scoped>
.tel:hover {
  cursor: url(/img/phone-forward.svg) 10 10, none;
  text-decoration: none;
}
.tel {
  display: block;
  color: #000;
}
#maintable.v-data-table >>> tr {
  outline: 2px solid transparent;
}

#maintable.v-data-table >>> tr:hover,
#maintable.v-data-table >>> tr.v-data-table__selected {
  border: 2px solid #000;
  cursor: pointer;
}
#maintable.v-data-table >>> tr.v-data-table__selected {
  border-bottom: transparent !important;
}
#maintable.v-data-table >>> tr.v-data-table__expanded tr:hover {
  border: none;
}
#maintable >>> .text-start {
  padding: 0 !important;
}
.blackborder {
  border: 2px solid #000;
  border-top: transparent !important;
}
</style>
