<template>
  <v-card height="100%" class="pa-5">
    <v-progress-linear
      :active="loading"
      indeterminate
      color="purple"
    ></v-progress-linear>

    <v-list>
      <div>
        <v-row class="direction-column">
          <v-col>
            <div class="mb-5">
              <b>Отобр: {{ checkedLids.length }} z {{ lids.length }}</b>
            </div>
            <v-select
              v-model="filterOffices"
              :items="officeItemsFromLids"
              outlined
              rounded
              multiple
              label="Office"
              @change="
                getGroup();
                getUsers();
                getStatuses();
              "
              style="width: 20rem"
            >
            </v-select>
            <v-select
              v-model="filterGroups"
              :items="groupItemsFromLids"
              outlined
              rounded
              multiple
              label="Group"
              style="width: 20rem"
            >
            </v-select>
            <v-select
              v-model="filterUsers"
              :items="usersItemsFromLids"
              outlined
              rounded
              multiple
              label="Users"
              style="width: 20rem"
            >
            </v-select>
            <v-select
              ref="filterStatus"
              color="red"
              v-model="filterStatus"
              :items="statusesItemsFromLids"
              :item-value="'id'"
              :item-text="'name'"
              outlined
              rounded
              :multiple="true"
              :menu-props="{ maxHeight: '80vh' }"
              label="Статусы"
              style="width: 20rem"
              clearable
            >
              <template v-slot:selection="{ item, index }">
                <span v-if="index === 0">{{ item.name }} </span>
                <span v-if="index === 1" class="grey--text text-caption">
                  +
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
                {{ item.name }} {{ item.count ? ` (${item.count})` : "" }}
              </template>
            </v-select>
          </v-col>
          <v-col>
            <p v-if="userids.length">Отбор: {{ userids.length }}</p>
            <v-btn class="btn ma-3" @click="setUserIds">Назначить</v-btn>
            <div style="height: 70vh" class="overflow-x-auto">
              <v-expansion-panels>
                <v-expansion-panel v-for="office in offices" :key="office.id">
                  <v-expansion-panel-header>
                    <p class="title">{{ office.name }}</p>
                  </v-expansion-panel-header>
                  <v-expansion-panel-content>
                    <v-expansion-panels v-model="akkvalue[office.id]">
                      <v-expansion-panel
                        v-for="item in groups.filter(
                          (g) => g.office_id == office.id
                        )"
                        :key="item.group_id"
                      >
                        <v-expansion-panel-header>
                          {{ item.fio }}
                          <div></div>
                        </v-expansion-panel-header>
                        <v-expansion-panel-content>
                          <v-col
                            v-for="user in users.filter(function (i) {
                              return i.group_id == item.group_id;
                            })"
                            :key="user.id"
                          >
                            <input
                              type="checkbox"
                              :id="user.id"
                              :value="user.id"
                              v-model="userids"
                            />
                            <label :for="user.id"
                              >{{ user.fio }}
                              <v-badge
                                :content="user.hmlids"
                                :value="user.hmlids"
                                :color="usercolor(user)"
                                overlap
                              >
                              </v-badge>
                            </label>
                          </v-col>
                        </v-expansion-panel-content>
                      </v-expansion-panel>
                    </v-expansion-panels>
                  </v-expansion-panel-content>
                </v-expansion-panel>
              </v-expansion-panels>
            </div>
          </v-col>
        </v-row>
      </div>
    </v-list>
  </v-card>
</template>

<script>
import axios from "axios";
import _ from "lodash";
export default {
  name: "selectUsersnew",
  props: {
    user: {},
    lids: {
      type: Array,
      default: () => [],
    },
  },
  data: () => ({
    users: [],
    offices: [],
    groups: [],
    statuses: [],
    loading: false,
    akkvalue: [],
    userids: [],
    panel: [],
    groupByOffice: [],
    filterOffices: [],
    filterGroups: [],
    filterUsers: [],
    filterStatus: [],
  }),
  mounted() {
    this.getOffices();
    this.getStatuses();
  },
  computed: {
    checkedLids() {
      return Array.isArray(this.lids)
        ? this.lids.filter((lid) => {
            const officeOk =
              !this.filterOffices.length ||
              this.filterOffices.includes(lid.office_id);
            const groupOk =
              !this.filterGroups.length ||
              this.filterGroups.includes(lid.group_id);
            const userOk =
              !this.filterUsers.length ||
              this.filterUsers.includes(lid.user_id);
            const statusOk =
              !this.filterStatus.length ||
              this.filterStatus.includes(lid.status_id);
            return officeOk && groupOk && userOk && statusOk;
          })
        : [];
    },
    officeItemsFromLids() {
      // Get unique office_ids from this.lids and map to [{text: name, value: id}]
      const officeIds = Array.isArray(this.lids)
        ? [...new Set(this.lids.map((l) => l.office_id))]
        : [];
      return this.offices
        .filter((o) => officeIds.includes(o.id))
        .map((o) => ({
          text: o.name,
          value: o.id,
        }));
    },
    groupItemsFromLids() {
      // Only show groups for selected offices
      const selectedOfficeIds =
        this.filterOffices && this.filterOffices.length
          ? this.filterOffices
          : [];

      // Get unique group_ids from lids for selected offices
      const groupIds = Array.isArray(this.lids)
        ? [
            ...new Set(
              this.lids
                .filter((l) => selectedOfficeIds.includes(l.office_id))
                .map((l) => l.group_id)
            ),
          ]
        : [];

      // Filter users who are groups (group_id == id) and belong to selected offices
      const groupUsers = this.users.filter(
        (u) =>
          groupIds.includes(u.group_id) &&
          u.group_id === u.id &&
          selectedOfficeIds.includes(u.office_id)
      );

      // Remove duplicates by group_id (user.id)
      const uniqueGroups = [];
      const seen = new Set();
      for (const user of groupUsers) {
        if (!seen.has(user.id)) {
          uniqueGroups.push({
            text: user.fio,
            value: user.id,
          });
          seen.add(user.id);
        }
      }

      return uniqueGroups;
    },
    usersItemsFromLids() {
      // Показывать пользователей только из выбранных групп this.filterGroups
      const userIds = Array.isArray(this.lids)
        ? [...new Set(this.lids.map((l) => l.user_id))]
        : [];
      // Если выбраны группы, фильтруем по ним
      const selectedGroups =
        Array.isArray(this.filterGroups) && this.filterGroups.length
          ? this.filterGroups
          : null;
      return this.users
        .filter((u) => userIds.includes(u.id))
        .filter((u) => !selectedGroups || selectedGroups.includes(u.group_id))
        .map((u) => ({
          text: u.fio,
          value: u.id,
        }));
    },
    statusesItemsFromLids() {
      // Фильтруем lids по выбранным офисам, группам, пользователям
      let filteredLids = this.lids;

      if (this.filterOffices && this.filterOffices.length) {
        filteredLids = filteredLids.filter((lid) =>
          this.filterOffices.includes(lid.office_id)
        );
      }
      if (this.filterGroups && this.filterGroups.length) {
        filteredLids = filteredLids.filter((lid) =>
          this.filterGroups.includes(lid.group_id)
        );
      }
      if (this.filterUsers && this.filterUsers.length) {
        filteredLids = filteredLids.filter((lid) =>
          this.filterUsers.includes(lid.user_id)
        );
      }

      // Count statuses in filteredLids
      const statusCounts = filteredLids.reduce((acc, lid) => {
        if (!acc[lid.status_id]) {
          // Find status in this.statuses by id
          const status = this.statuses.find((s) => s.id === lid.status_id);
          acc[lid.status_id] = {
            name: status.name,
            count: 0,
          };
        }
        acc[lid.status_id].count++;
        return acc;
      }, {});

      return Object.entries(statusCounts).map(([id, { name, count }]) => ({
        id: parseInt(id),
        name,
        count,
        color: this.getStatusColor(id),
      }));
    },
  },
  methods: {
    getStatusColor(statusId) {
      console.log(statusId);
      const status = this.statuses.find((s) => s.id === statusId);
      return status ? status.color : "#fff"; // Default color if not found
    },
    setGroup(group_id, level, e) {
      let au = [];
      if (level == 0) {
        au = this.users
          .filter((u) => {
            return u.group_id == group_id;
          })
          .map(({ id }) => id);
      } else {
        au = this.users
          .filter((u) => {
            return u.group_id == group_id && u.level == level;
          })
          .map(({ id }) => id);
      }

      if (e.target.checked) {
        this.userids = this.userids.concat(au);
      } else {
        this.userids = this.userids.filter((u) => {
          return !au.includes(u);
        });
      }
    },
    setUserIds() {
      this.$emit("getUserIds", this.userids);
      this.userids = [];
    },
    getOffices() {
      let self = this;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;

          if (self.$props.user.office_id > 0) {
            self.offices = self.offices.filter(
              (o) => o.id == self.$props.user.office_id
            );
          }
          self.getUsers();
        })
        .catch((error) => {
          console.log("error", error);
        });
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/users")
        .then((res) => {
          let data = res.data;
          self.users = data.map(
            ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              office_id,
              level,
              active,
            }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              office_id,
              level,
              active,
            })
          );
          self.loading = false;
          self.users = self.users.filter((el) => {
            return el.active == 1;
          });
          self.groups = _.filter(self.users, function (o) {
            return o.group_id == o.id;
          });
        })
        .catch((error) => console.log(error));
    },
    getUsers_del() {
      let self = this;
      this.loading = true;
      axios
        .post("/api/getusers", [])
        .then((res) => {
          let data = res.data;
          self.users = data.map(
            ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              office_id,
              level,
            }) => ({
              name,
              id,
              role_id,
              fio,
              hmlids,
              group_id,
              office_id,
              level,
            })
          );
          self.loading = false;
          // self.users = self.users.filter((u) => {
          //   return ![1144, 1036].includes(u.group_id);
          // });
          // self.users = self.users.filter((u) => {
          //   return u.fio.indexOf("_") == -1;
          // });
          // self.groupByOffice = _.groupBy(self.users, "office_id");
          self.groups = _.filter(self.users, function (o) {
            return o.group_id == o.id;
          });
        })
        .catch((error) => console.log(error));
    },
    deb(u) {
      console.log(u);
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
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
  },
};
</script>

<style>
.v-expansion-panel-header--active > div > label {
  font-weight: bold;
}
</style>
