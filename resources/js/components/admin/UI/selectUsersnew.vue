<template>
  <v-card height="100%" class="pa-5">
    <v-progress-linear
      :active="loading"
      indeterminate
      color="purple"
    ></v-progress-linear>
    <v-btn
      icon
      class="dialog-close-btn"
      style="position: absolute; right: -8px; top: -8px; z-index: 10"
      @click="close()"
    >
      <v-icon>mdi-close</v-icon>
    </v-btn>
    <v-list>
      <div>
        <v-row class="direction-column">
          <v-col>
            <div class="mb-5">
              <b>Отобр: {{ checkedLids.length }} из {{ lids.length }}</b>
            </div>
            <div>
              <ul>
                <li v-for="(file, idx) in files" :key="idx">{{ file }}</li>
              </ul>
            </div>
            <v-select
              v-model="filterOffices"
              :items="officeItemsFromLids"
              outlined
              rounded
              multiple
              label="Офис"
              @change="
                filterGroups = [];
                filterUsers = [];
                filterStatus = [];
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
              label="Группа"
              style="width: 20rem"
              @change="
                filterUsers = [];
                filterStatus = [];
              "
            >
            </v-select>
            <v-select
              v-model="filterUsers"
              :items="usersItemsFromLids"
              outlined
              rounded
              multiple
              @change="filterStatus = []"
              label="Менеджеры"
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
            <v-spacer></v-spacer>
            <div class="w-50">
              <template
                v-for="(i, x) in statuses.filter((s) =>
                  filterStatus.includes(s.id)
                )"
              >
                <div class="status_wrp mb-1" :key="x">
                  <b
                    :style="{
                      background: i.color,
                      outline: '1px solid ' + i.color,
                    }"
                    >{{ i.count }}</b
                  >
                  <span>{{ i.name }}</span>
                </div>
              </template>
            </div>
            <!-- for selected users -->
            <div v-if="userids.length > 0" class="selected-users-tree mb-4">
              <p class="mb-2">Выбранные пользователи:</p>
              <div
                v-for="office in selectedOfficesWithUsers"
                :key="office.id"
                class="office-section mb-3"
              >
                <div class="office-title font-weight-bold mb-2">
                  {{ office.name }}
                </div>
                <div
                  v-for="group in groups.filter(
                    (g) =>
                      g.office_id === office.id &&
                      selectedUsersByGroup[g.id] &&
                      selectedUsersByGroup[g.id].length > 0
                  )"
                  :key="group.id"
                  class="group-section ml-4 mb-2"
                >
                  <div class="group-title font-weight-medium mb-1">
                    <v-icon class="mr-2">mdi-account-group</v-icon
                    >{{ group.fio }}
                  </div>
                  <div class="user-list">
                    <div
                      v-for="user in selectedUsersByGroup[group.id]"
                      :key="user.id"
                      class="user-item ml-4 d-flex"
                      style="gap: 1rem"
                    >
                      <v-chip small color="primary" class="ml-2">
                        {{ userLeadCounts[user.id] || 0 }}
                      </v-chip>
                      <span>{{ user.fio }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </v-col>
          <v-col>
            <v-row>
              <v-col>
                <v-select
                  ref="resetStatus"
                  label="На статус"
                  v-model="resetOnStatus"
                  :items="statuses"
                  item-text="name"
                  item-value="id"
                  outlined
                  rounded
                >
                  <template v-slot:selection="{ item, index }">
                    <span v-if="index === 0">{{ item.name }} </span>
                    <span v-if="index === 1" class="grey--text text-caption">
                      (+{{ resetOnStatus.length - 1 }} )
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
                <v-switch
                  class="mt-0"
                  v-model="clearLog"
                  :label="`Удалять логи: ${clearLog.toString()}`"
                  hide-details
                ></v-switch
              ></v-col>
              <v-btn class="btn ma-3" @click="setUserIds">Назначить</v-btn>
            </v-row>
            <p>
              <b v-if="userids.length">Получателей {{ userids.length }} </b>
              по
              <input
                v-model="hmset"
                style="width: 60px; margin-left: 10px; border: 1px solid #ccc"
              />
              <span v-if="hmset > 0"> осталось: {{ hmleft }}</span>
            </p>
            <div style="height: 70vh" class="overflow-x-auto">
              <v-expansion-panels v-model="panel">
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

                              <input
                                v-if="userids.includes(user.id)"
                                v-model="userLeadCounts[user.id]"
                                style="
                                  width: 60px;
                                  margin-left: 10px;
                                  border: 1px solid #ccc;
                                "
                              />
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
      type: [Array, Object],
      default: () => [],
    },
    files: {
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
    userLeadCounts: {},
    resetOnStatus: 8,
    clearLog: false,
    hmset: 0, // Default value for hmset
    hmleft: 0, // Default value for hmleft
  }),
  mounted() {
    this.clearFilters();
    this.getOffices();
    this.getStatuses();
  },

  beforeDestroy() {
    this.panel = [];
    this.userids = [];
    this.filterOffices = [];
    this.filterGroups = [];
    this.filterUsers = [];
    this.filterStatus = [];
    this.hmleft = 0;
    this.hmset = 0;
  },
  watch: {
    lids: {
      handler() {
        this.hmleft = 0;
      },
      immediate: true,
    },
    userids: {
      handler(newVal) {
        const total = this.checkedLids.length;
        let counts = {};
        let left = total;

        if (this.hmset > 0) {
          newVal.forEach((uid, idx) => {
            // If user already has a custom value, keep it, else assign hmset
            if (this.userLeadCounts[uid] && this.userLeadCounts[uid] > 0) {
              counts[uid] = this.userLeadCounts[uid];
            } else {
              // For the last user, if left < hmset, assign left
              if (idx === newVal.length - 1 && left < this.hmset) {
                counts[uid] = left > 0 ? left : 0;
              } else {
                counts[uid] = left >= this.hmset ? this.hmset : 0;
              }
            }
            left -= counts[uid];
          });
          // Remove users that are no longer selected
          Object.keys(this.userLeadCounts).forEach((uid) => {
            if (!newVal.includes(Number(uid))) {
              delete this.userLeadCounts[uid];
            }
          });
        } else {
          // Default calculation (even distribution with max difference of 3)
          const n = newVal.length;
          if (n === 0) {
            left = 0;
          } else {
            // Базовое количество для каждого пользователя
            const base = Math.floor(total / n);
            const remainder = total % n;

            // Создаем массив с базовым распределением
            let countsArr = Array(n).fill(base);

            // Добавляем остаток к первым remainder пользователям
            for (let i = 0; i < remainder; i++) {
              countsArr[i]++;
            }

            // Проверяем разницу между максимальным и минимальным значением
            const maxCount = Math.max(...countsArr);
            const minCount = Math.min(...countsArr);
            const difference = maxCount - minCount;

            // Если разница больше 3, перераспределяем
            if (difference > 3) {
              // Находим пользователей с максимальным количеством
              const maxUsers = [];
              const minUsers = [];

              countsArr.forEach((count, idx) => {
                if (count === maxCount) {
                  maxUsers.push(idx);
                } else if (count === minCount) {
                  minUsers.push(idx);
                }
              });

              // Перераспределяем лишние лиды
              let excessLeads = 0;
              maxUsers.forEach((idx) => {
                const excess = countsArr[idx] - (minCount + 3);
                if (excess > 0) {
                  excessLeads += excess;
                  countsArr[idx] -= excess;
                }
              });

              // Распределяем лишние лиды среди пользователей с минимальным количеством
              let redistIndex = 0;
              while (excessLeads > 0 && redistIndex < n) {
                if (countsArr[redistIndex] < minCount + 3) {
                  const canAdd = Math.min(
                    excessLeads,
                    minCount + 3 - countsArr[redistIndex]
                  );
                  countsArr[redistIndex] += canAdd;
                  excessLeads -= canAdd;
                }
                redistIndex++;
              }

              // Если всё ещё есть лишние лиды, распределяем их равномерно
              redistIndex = 0;
              while (excessLeads > 0 && redistIndex < n) {
                if (countsArr[redistIndex] === minCount + 3) {
                  // Можем добавить ещё, но не больше чем разница в 3
                  const currentMin = Math.min(...countsArr);
                  if (countsArr[redistIndex] - currentMin < 3) {
                    countsArr[redistIndex]++;
                    excessLeads--;
                  }
                }
                redistIndex++;

                // Если прошли всех пользователей, начинаем заново
                if (redistIndex >= n) {
                  redistIndex = 0;
                }
              }
            }

            // Присваиваем значения counts по userids
            newVal.forEach((uid, idx) => {
              counts[uid] = countsArr[idx] || 0;
            });

            left = 0;
          }
        }

        this.userLeadCounts = counts;
        this.hmleft = left < 0 ? 0 : left;
      },
      immediate: true,
    },
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
          text: `${o.name} - ${
            this.lids.filter((l) => l.office_id === o.id).length
          } (${
            this.lids.length
              ? Math.round(
                  (this.lids.filter((l) => l.office_id === o.id).length /
                    this.lids.length) *
                    100
                )
              : 0
          }%)`,
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
            text: `${user.fio} - ${
              this.lids.filter((l) => l.group_id === user.id).length
            } (${
              this.lids.length
                ? Math.round(
                    (this.lids.filter((l) => l.group_id === user.id).length /
                      this.lids.length) *
                      100
                  )
                : 0
            }%)`,
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
          text: `${u.fio} - ${
            this.lids.filter((l) => l.user_id === u.id).length
          } (${
            this.lids.length
              ? Math.round(
                  (this.lids.filter((l) => l.user_id === u.id).length /
                    this.lids.length) *
                    100
                )
              : 0
          }%)`,
          value: u.id,
        }));
    },
    statusesItemsFromLids() {
      // Убеждаемся что lids это массив
      let filteredLids = Array.isArray(this.lids) ? [...this.lids] : [];

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

      // Безопасный reduce с проверкой на массив
      const statusCounts = filteredLids.reduce((acc, lid) => {
        if (!acc[lid.status_id]) {
          // Найти статус в this.statuses по id
          const status = this.statuses.find((s) => s.id === lid.status_id);
          acc[lid.status_id] = {
            name: status ? status.name : "Unknown Status", // защита от undefined
            count: 0,
          };
        }
        acc[lid.status_id].count++;
        return acc;
      }, {});

      return Object.entries(statusCounts).map(
        ([status_id, { name, count }]) => ({
          id: parseInt(status_id),
          status_id: parseInt(status_id),
          name,
          count,
          color: this.getStatusColor(status_id),
        })
      );
    },
    selectedUsersByOffice() {
      const result = {};
      this.offices.forEach((office) => {
        result[office.id] = this.users.filter(
          (user) =>
            this.userids.includes(user.id) && user.office_id === office.id
        );
      });
      return result;
    },
    selectedOfficesWithUsers() {
      return this.offices.filter(
        (office) =>
          this.selectedUsersByOffice[office.id] &&
          this.selectedUsersByOffice[office.id].length > 0
      );
    },
    selectedUsersByGroup() {
      const result = {};
      this.groups.forEach((group) => {
        result[group.id] = this.users.filter(
          (user) => this.userids.includes(user.id) && user.group_id === group.id
        );
      });
      return result;
    },
  },
  methods: {
    clearFilters() {
      this.filterOffices = [];
      this.filterGroups = [];
      this.filterUsers = [];
      this.filterStatus = [];
      this.userids = [];
      this.userLeadCounts = {};
      this.hmleft = 0;
      this.hmset = 0;
    },
    getStatusColor(statusId) {
      // statusId can be string or number, ensure both are compared as numbers
      const id = typeof statusId === "string" ? parseInt(statusId) : statusId;
      const status = this.statuses.find((s) => Number(s.id) === id);
      return status && status.color ? status.color : "#fff"; // Default color if not found
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
            return u.group_id == group_id;
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
      const result = this.userids.map((uid) => ({
        user_id: uid,
        count: this.userLeadCounts[uid] || 0,
      }));
      this.$emit("getUserIds", {
        users: result,
        checkedLids: this.checkedLids,
        resetOnStatus: this.resetOnStatus,
        clearLog: this.clearLog,
      });
      this.panel = [];
      this.userids = [];
      this.filterOffices = [];
      this.filterGroups = [];
      this.filterUsers = [];
      this.filterStatus = [];
      this.hmleft = 0;
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
    close() {
      this.panel = [];
      this.userids = [];
      this.filterOffices = [];
      this.filterGroups = [];
      this.filterUsers = [];
      this.filterStatus = [];
      this.hmleft = 0;
      this.$emit("close");
    },
  },
};
</script>

<style>
.v-expansion-panel-header--active > div > label {
  font-weight: bold;
}
</style>
