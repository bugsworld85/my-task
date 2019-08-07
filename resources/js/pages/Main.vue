<template>
  <main class="col-md-12">
    <div class="row">
      <div class="col-md-3">
        <h2>Calendar</h2>
        <form @submit.prevent="createEvent($event)">
          <p>
            <label>Event</label>
            <input type="text" ref="event" class="form-control" required />
          </p>
          <p class="row">
            <span class="col-md-12 input-group input-daterange">
              <input
                type="text"
                ref="from"
                id="from"
                class="form-control"
                required
                autocomplete="off"
              />
              <span class="input-group-addon">to</span>
              <input type="text" ref="to" id="to" class="form-control" required autocomplete="off" />
            </span>
          </p>
          <p>
            <input type="checkbox" ref="mon" value="mon" />Mon
            <input type="checkbox" ref="tue" value="tue" />Tue
            <input type="checkbox" ref="wed" value="wed" />Wed
            <input type="checkbox" ref="thu" value="thu" />Thu
            <input type="checkbox" ref="fri" value="fri" />Fri
            <input type="checkbox" ref="sat" value="sat" />Sat
            <input type="checkbox" ref="sun" value="sun" />Sun
          </p>
          <p>
            <input type="submit" value="Save" class="btn btn-primary" />
          </p>
        </form>
      </div>
      <div class="col-md-9">
        <h1>{{ currentMonth.format('MMMM YYYY') }}</h1>
        <ul>
          <li
            v-for="n in currentMonth.daysInMonth()"
            :key="n"
            v-bind:class="{ 'has-event': hasEvent(n) }"
          >
            <span>{{ currentMonth.date(n).format('D ddd') }}</span>
            <span>{{ displayEvent(n) }}</span>
          </li>
        </ul>
      </div>
      <notifications group="status" />
    </div>
  </main>
</template>

<script>
require("bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css");
import axios from "axios";
import moment from "moment";
import Datepicker from "bootstrap-datepicker";
export default {
  name: "main-content",
  mixins: {
    moment: moment
  },
  data() {
    return {
      currentMonth: moment(),
      events: []
    };
  },
  mounted() {
    $(".input-daterange").datepicker({});
    this.getEvents();
  },
  methods: {
    hasEvent(date) {
      return this.events.some((item, i) => {
        return (
          item.schedule === this.currentMonth.date(date).format("YYYY-MM-DD")
        );
      });
    },
    displayEvent(date) {
      let schedule = this.events.find((item, i) => {
        return (
          item.schedule === this.currentMonth.date(date).format("YYYY-MM-DD")
        );
      });
      return schedule ? schedule.event : null;
    },
    /**
     * Get events from database
     */
    async getEvents(month = null) {
      await axios({
        url: "/api/events",
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-Request-With": "XMLHttpRequest"
        },
        params: {
          month
        }
      })
        .then(response => {
          this.events = response.data;
        })
        .catch(({ response }) => {
          if (response && response.status === 422) {
            this.$notify({
              group: "status",
              text: response.data.error,
              type: "error"
            });
          }
        });
    },
    /**
     * Create the event on form submit
     */
    async createEvent(e) {
      // this is just to insure the form does not reload the page.
      e.preventDefault();
      // prepare data here
      let data = {
        event: this.$refs.event.value,
        from: this.$refs.from.value,
        to: this.$refs.to.value,
        dates: []
      };

      let from = moment(data.from);
      let to = moment(data.to);

      while (from <= to) {
        // update all dates within the range
        data.dates.push({
          date: from.format("YYYY-MM-DD"),
          isSelected: this.$refs[from.format("ddd").toLowerCase()].checked
        });
        from.add(1, "days"); // iterate
      }
      // call api endpoint
      await axios({
        url: "/api/event/create",
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Request-With": "XMLHttpRequest"
        },
        data
      })
        .then(response => {
          this.getEvents();
          this.$notify({
            group: "status",
            text: "Successfully added schedule to calendar",
            type: "success"
          });
        })
        .catch(({ response }) => {
          if (response && response.status === 422) {
            this.$notify({
              group: "status",
              text: response.data.error,
              type: "error"
            });
          }
        });
    }
  }
};
</script>

<style lang="css" scoped>
main input[type="checkbox"] {
  margin: 0 3px 0 15px;
  cursor: pointer;
}
.input-group input#from {
  border-top-left-radius: 0.25rem;
  border-bottom-left-radius: 0.25rem;
}
.input-group input#to {
  border-top-right-radius: 0.25rem;
  border-bottom-right-radius: 0.25rem;
}
.input-group .input-group-addon {
  padding: 5px;
  border-top: 1px solid #ced4da;
  border-bottom: 1px solid #ced4da;
}
main ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
main ul li {
  padding: 5px 10px;
  border-bottom: 1px solid #ced4da;
}
main ul li.has-event {
  background-color: #d6ffdf;
}
main ul li span {
  display: inline-block;
  vertical-align: middle;
}
main ul li span:first-child {
  width: 100px;
  text-align: right;
}
main ul li span:last-child {
  padding-left: 20px;
}
</style>